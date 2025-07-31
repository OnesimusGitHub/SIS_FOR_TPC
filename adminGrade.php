<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: adminLogin.php");
    exit;
}

// Database connection
$database = "sis";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch grades grouped by grade level and strand
    $sql = "SELECT g.shsstud_ID, g.first_grading, g.second_grading, g.third_grading, g.fourth_grading, 
                   g.teacher_name, g.teacher_field, s.shstud_firstname, s.shstud_lastname, sec.section_name, 
                   t.grade, str.strand_name
            FROM tblgrades g
            INNER JOIN tblshsstudent s ON g.shsstud_ID = s.shsstud_ID
            INNER JOIN teachrinf t ON g.teacher_name = t.teachername
            INNER JOIN tblshssection sec ON s.section_ID = sec.section_ID
            INNER JOIN tblstrand str ON s.strand_ID = str.strand_ID
            WHERE g.archived IS NULL
            ORDER BY str.strand_name ASC, s.shstud_lastname ASC";
    $stmt = $pdo->query($sql);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group grades by grade level and strand
    $groupedGrades = [];
    foreach ($grades as $grade) {
        $strandName = $grade['strand_name'];
        $groupedGrades[$strandName][] = $grade;
    }

} catch (PDOException $e) {
    die("<h1>ERROR: Could not connect. " . htmlspecialchars($e->getMessage()) . "</h1>");
}

unset($pdo);

// Function to calculate final grade and remarks
function calculateFinalGrade($grades) {
    $finalGrade = array_sum($grades) / count($grades);
    $remark = $finalGrade >= 75 ? 'Passed' : 'Failed';
    return ['finalGrade' => $finalGrade, 'remark' => $remark];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    if (!empty($_POST['save_grade']) && !empty($_POST['grades'][$_POST['save_grade']])) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $studentID = $_POST['save_grade'];
            $grades = $_POST['grades'][$studentID];

            // Validate grades array
            if (!is_array($grades) || count($grades) !== 4) {
                die("<h1>Invalid grades data. Please ensure all fields are filled out correctly.</h1>");
            }

            // Calculate the final grade
            $finalGrade = array_sum($grades) / 4;

            $sqlUpdate = "INSERT INTO tblgrades (shsstud_ID, first_grading, second_grading, third_grading, fourth_grading, updated_at)
                          VALUES (:shsstud_ID, :first_grading, :second_grading, :third_grading, :fourth_grading, CURRENT_TIMESTAMP)
                          ON DUPLICATE KEY UPDATE 
                          first_grading = :first_grading, 
                          second_grading = :second_grading, 
                          third_grading = :third_grading, 
                          fourth_grading = :fourth_grading,
                          updated_at = CURRENT_TIMESTAMP";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':shsstud_ID' => $studentID,
                ':first_grading' => $grades['first'],
                ':second_grading' => $grades['second'],
                ':third_grading' => $grades['third'],
                ':fourth_grading' => $grades['fourth']
            ]);
        } catch (PDOException $e) {
            die("<h1>ERROR: Could not update grades. " . htmlspecialchars($e->getMessage()) . "</h1>");
        }
        unset($pdo);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        die("<h1>Invalid form submission. Please ensure all fields are filled out correctly.</h1>");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all_grades'])) {
    if (!empty($_POST['grades']) && is_array($_POST['grades'])) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach ($_POST['grades'] as $studentID => $grades) {
                // Validate grades array
                if (!is_array($grades) || count($grades) !== 4) {
                    continue;
                }

                // Calculate the final grade
                $finalGrade = array_sum($grades) / 4;

                $sqlUpdate = "INSERT INTO tblgrades (shsstud_ID, first_grading, second_grading, third_grading, fourth_grading, updated_at)
                              VALUES (:shsstud_ID, :first_grading, :second_grading, :third_grading, :fourth_grading, CURRENT_TIMESTAMP)
                              ON DUPLICATE KEY UPDATE 
                              first_grading = :first_grading, 
                              second_grading = :second_grading, 
                              third_grading = :third_grading, 
                              fourth_grading = :fourth_grading,
                              updated_at = CURRENT_TIMESTAMP";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':shsstud_ID' => $studentID,
                    ':first_grading' => $grades['first'],
                    ':second_grading' => $grades['second'],
                    ':third_grading' => $grades['third'],
                    ':fourth_grading' => $grades['fourth']
                ]);
            }
        } catch (PDOException $e) {
            die("<h1>ERROR: Could not update grades. " . htmlspecialchars($e->getMessage()) . "</h1>");
        }
        unset($pdo);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        die("<h1>Invalid form submission. Please ensure all fields are filled out correctly.</h1>");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_grade'])) {
    if (!empty($_POST['delete_grade'])) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $studentID = $_POST['delete_grade'];

            $sqlDelete = "DELETE FROM tblgrades WHERE shsstud_ID = :shsstud_ID";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->execute([':shsstud_ID' => $studentID]);
        } catch (PDOException $e) {
            die("<h1>ERROR: Could not delete grade. " . htmlspecialchars($e->getMessage()) . "</h1>");
        }
        unset($pdo);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        die("<h1>Invalid request. Please try again.</h1>");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_grade'])) {
    if (!empty($_POST['archive_grade'])) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $studentID = $_POST['archive_grade'];

            // Archive the grade by moving it to an archive table or marking it as archived
            $sqlArchive = "UPDATE tblgrades SET archived = 1 WHERE shsstud_ID = :shsstud_ID";
            $stmtArchive = $pdo->prepare($sqlArchive);
            $stmtArchive->execute([':shsstud_ID' => $studentID]);
        } catch (PDOException $e) {
            die("<h1>ERROR: Could not archive grade. " . htmlspecialchars($e->getMessage()) . "</h1>");
        }
        unset($pdo);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        die("<h1>Invalid request. Please try again.</h1>");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="studentPROFILE.css">
    <title>Admin Grades</title>
    <style>
        /* General Styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
            color: #333;
        }
        .container {
            width: 95%;
            max-width: 1500px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 20px;
        }
        .header {
            background-color: #0056b3;
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 16px;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        th {
            background-color: #0056b3;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #e6f7ff;
            transition: background-color 0.3s ease;
        }
        td {
            color: #555;
        }
        input[type="number"] {
            width: 80px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: center;
        }
        input[type="number"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .save-button, .delete-button, .save-all-button {
            display: inline-block;
            margin: 5px 0;
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .save-button {
            background-color: #28a745;
            color: white;
        }
        .save-button:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .delete-button {
            background-color: #dc3545;
            color: white;
        }
        .delete-button:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }
        .save-all-button {
            background-color: #17a2b8;
            color: white;
        }
        .save-all-button:hover {
            background-color: #138496;
            transform: scale(1.05);
        }
        .no-data {
            text-align: center;
            font-size: 18px;
            color: #666;
            padding: 20px;
        }
        .remarks {
            font-weight: bold;
        }
        .remarks.passed {
            color: green;
        }
        .remarks.failed {
            color: red;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .search-bar {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
        .search-bar input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .side-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 270px;
            height: 100%;
            background-color: #0056b3;
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            overflow-y: auto;
        }
        .side-menu.open {
            display: block;
        }
        .side-menu ul {
            list-style: none;
            padding: 0;
        }
        .side-menu ul li {
            margin: 15px 0;
        }
        .side-menu ul li a {
            text-decoration: none;
            display: block;
            background-color: #cce0ff;
            color: #003366;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .side-menu ul li a:hover {
            background-color: #b3ccff;
        }
        @media (max-width: 768px) {
            .grades-table th, .grades-table td {
                font-size: 12px;
                padding: 10px;
            }
            input[type="number"] {
                width: 60px;
            }
            .save-button, .delete-button, .save-all-button {
                padding: 8px 15px;
                font-size: 12px;
            }
        }
    </style>
    <script>
        function fetchGrades(searchQuery = "") {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", `fetchGrades.php?archived=null&search=${encodeURIComponent(searchQuery)}`, true); // Fetch grades with optional search query
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const gradesData = JSON.parse(xhr.responseText);
                    const gradesTableBody = document.getElementById("gradesTableBody");

                    gradesTableBody.innerHTML = ""; // Clear existing rows
                    gradesData.forEach((grade) => {
                        const row = document.createElement("tr");
                        row.setAttribute("data-student-id", grade.shsstud_ID);

                        row.innerHTML = `
                            <td>${grade.teacher_name}</td>
                            <td>${grade.teacher_field || "N/A"}</td>
                            <td>${grade.grade || "N/A"}</td>
                            <td>${grade.section_name || "N/A"}</td>
                            <td>${grade.shstud_firstname}</td>
                            <td>${grade.shstud_lastname}</td>
                            <td><input type="number" name="grades[${grade.shsstud_ID}][first]" class="first-grading" value="${grade.first_grading}" step="0.01" required></td>
                            <td><input type="number" name="grades[${grade.shsstud_ID}][second]" class="second-grading" value="${grade.second_grading}" step="0.01" required></td>
                            <td><input type="number" name="grades[${grade.shsstud_ID}][third]" class="third-grading" value="${grade.third_grading}" step="0.01" required></td>
                            <td><input type="number" name="grades[${grade.shsstud_ID}][fourth]" class="fourth-grading" value="${grade.fourth_grading}" step="0.01" required></td>
                            <td class="final-grade">${(grade.final_grade || 0).toFixed(2)}</td>
                            <td class="action-buttons">
                                <button type="submit" name="archive_grade" value="${grade.shsstud_ID}" class="save-button" style="background-color: #ffc107; color: black;">Archive</button>
                            </td>
                        `;
                        gradesTableBody.appendChild(row);
                    });
                }
            };
            xhr.send();
        }

        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById("searchInput");
            searchInput.addEventListener("input", function () {
                fetchGrades(this.value); // Fetch grades dynamically as the user types
            });

            fetchGrades(); // Initial fetch
        });

        setInterval(fetchGrades, 5000); // Fetch grades every 5 seconds
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const burgerMenu = document.getElementById('burger-menu');
        const sideMenu = document.getElementById('side-menu');
        const closeMenu = document.getElementById('close-menu');

        function toggleMenu() {
            sideMenu.style.display = sideMenu.style.display === 'block' ? 'none' : 'block';
        }

        burgerMenu.addEventListener('click', toggleMenu);
        closeMenu.addEventListener('click', toggleMenu);
    });
    </script>
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <div class="logo-section">
            <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
        </div>
        <div class="user-section">
            <span class="user-role">Admin</span>
            <form action="" method="POST" style="display: inline;">
                <button type="button" class="logout-button" onclick="showLogoutModal()">Logout</button>
            </form>
        </div>
    </div>
    <br>
    <div class="sub-main">
        <div class="sub-header">
            <div class="menu-icon" id="burger-menu">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
            </div>
            <div class="home-section">
                <img class="img-home" src="TPC-IMAGES/student.png" alt="Home Icon" class="home-icon">
                <span class="home-text">Grades</span>
            </div>
        </div>
    </div>
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li class="menu-section-title">MY</li>
            <li><a href="admin_dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="adminPROFILE.php" class="menu-item">Profile</a></li>
            <li class="menu-section-title">ADD ACCOUNTS</li>
            <li><a href="admin_student_addSHS.php" class="menu-item">Add SHS Student</a></li>
            <li><a href="admin_instructor_add.php" class="menu-item">Add Instructor</a></li>
            <li><a href="admin_addAdmin.php" class="menu-item">Add Admin</a></li>
            <li><a href="admin_cashier_add.php" class="menu-item">Add Cashier</a></li>
            <li class="menu-section-title">ACCOUNTS</li>
            <li><a href="manageStudent.php" class="menu-item">Manage Students</a></li>
            <li><a href="adminAccounts.php" class="menu-item">Manage Admins</a></li>
            <li><a href="registrarAccounts.php" class="menu-item">Manage Cashiers</a></li>
            <li class="menu-section-title">ANNOUNCEMENTS</li>
            <li><a href="adminHome.php" class="menu-item">Announcements</a></li>
            <li class="menu-section-title">ANALYTICS</li>
            <li><a href="shonget.php" class="menu-item">Analytics</a></li>
            <li class="menu-section-title">GRADES</li>
            <li><a href="adminGrade.php" class="menu-item">Manage Grades</a></li>
        </ul>
    </nav>
</header>
<div class="container">
    <div class="header">
        <h1>Student Grades</h1>
        <p>Manage grades efficiently</p>
    </div>
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by name or section">
    </div>
    <div class="action-buttons" style="margin-bottom: 20px;">
        <a href="archivedGrades.php" class="save-all-button" style="text-decoration: none; text-align: center; margin-left:1289px;margin-top:-49px;">View Archived Grades</a>
    </div>
    <form method="POST">
        <?php foreach ($groupedGrades as $strandName => $students): ?>
            <h3>Strand: <?php echo htmlspecialchars($strandName); ?></h3>
            <table class="grades-table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Student First Name</th>
                        <th>Student Last Name</th>
                        <th>1st Grading</th>
                        <th>2nd Grading</th>
                        <th>3rd Grading</th>
                        <th>4th Grading</th>
                        <th>Final Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php 
                            $gradeValues = array_filter([
                                $student['first_grading'], 
                                $student['second_grading'], 
                                $student['third_grading'], 
                                $student['fourth_grading']
                            ], fn($value) => is_numeric($value) && $value !== null);
                            $finalGrade = !empty($gradeValues) ? array_sum($gradeValues) / count($gradeValues) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['teacher_field'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['section_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['shstud_firstname']); ?></td>
                            <td><?php echo htmlspecialchars($student['shstud_lastname']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_grading']); ?></td>
                            <td><?php echo htmlspecialchars($student['second_grading']); ?></td>
                            <td><?php echo htmlspecialchars($student['third_grading']); ?></td>
                            <td><?php echo htmlspecialchars($student['fourth_grading']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($finalGrade, 2)); ?></td>
                            <td>
                                <button type="submit" name="archive_grade" value="<?php echo $student['shsstud_ID']; ?>" class="save-button" style="background-color: #ffc107; color: black;">Archive</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
        <div class="action-buttons">
            <button type="submit" name="save_all_grades" class="save-all-button">Save All Grades</button>
        </div>
    </form>
</div>
</body>
</html>