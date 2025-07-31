<?php
session_start();

if (!isset($_SESSION["student_id"])) { // Updated session variable name to match studentPROFILE.php
    header("Location: studentLogin.php");
    exit;
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset(); // Clear all session variables
    session_destroy(); // Destroy the session
    header("Location: studentLogin.php"); // Redirect to login page
    exit;
}

// Define database connection variables
$database = "sis";
$username = "root";
$password = "";

// Retrieve student ID from session
$studentID = $_SESSION["student_id"]; // Updated session variable name

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch student details
    $sqlStudent = "SELECT shsstud_ID, shstud_firstname, shstud_lastname, strand_ID, section_ID 
                   FROM tblshsstudent 
                   WHERE shsstud_ID = :studentID";
    $stmtStudent = $pdo->prepare($sqlStudent);
    $stmtStudent->bindParam(':studentID', $studentID, PDO::PARAM_STR);
    $stmtStudent->execute();
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("<h1>Student not found.</h1>");
    }

    // Fetch the most recent grade and teacher information for the student
    $sqlGrades = "SELECT g.first_grading, g.second_grading, g.third_grading, g.fourth_grading, 
                         g.teacher_name, g.teacher_field
                  FROM tblgrades g
                  WHERE g.shsstud_ID = :studentID
                  ORDER BY g.updated_at DESC LIMIT 1"; // Ensure `updated_at` is used for ordering
    $stmtGrades = $pdo->prepare($sqlGrades);
    $stmtGrades->bindParam(':studentID', $studentID, PDO::PARAM_STR);
    $stmtGrades->execute();
    $grade = $stmtGrades->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<h1>ERROR: Could not connect. " . htmlspecialchars($e->getMessage()) . "</h1>");
}

unset($pdo);

// Function to calculate final grade and remarks
function calculateFinalGrade($grades) {
    if (!$grades || !is_array($grades)) { // Ensure $grades is a valid array
        return ['finalGrade' => 0, 'remark' => 'N/A'];
    }
    $finalGrade = array_sum($grades) / count($grades);
    $remark = $finalGrade >= 75 ? 'Passed' : 'Failed';
    return ['finalGrade' => $finalGrade, 'remark' => $remark];
}

// Map strand and section numbers to descriptive text
function getStrandText($strandNumber) {
    $strands = [
        1 => "STEM",
        2 => "ABM",
        3 => "HUMSS",
        4 => "GAS",
        5 => "TVL",
    ];
    return $strands[$strandNumber] ?? "Unknown Strand";
}

function getSectionText($sectionNumber) {
    $sections = [
        1 => "Section A",
        2 => "Section B",
        3 => "Section C",
    ];
    return $sections[$sectionNumber] ?? "Unknown Section";
}

// Convert strand and section numbers to text
$strandText = getStrandText($student['strand_ID']);
$sectionText = getSectionText($student['section_ID']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
            color: #333;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
           
        }
        .main-header {
            border-bottom: 1px solid #ccc;
        }
        .img-main {
            height: 8vh;
        }
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
        }
        .logo-section {
            display: flex;
            align-items: center;
        }
        .logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: black;
        }
        .user-section {
            display: flex;
            align-items: center;
        }
        .user-role {
            font-size: 16px;
            color: #0078D4;
            margin-right: 15px;
        }
        .logout-button {
            background-color: #0033cc;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #0055ff;
        }
        .sub-main {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50px;
            width: 100%;
        }
        .sub-header {
            width: 97%;
            display: flex;
            align-items: center;
            background-color: #0033cc;
            padding: 10px 20px;
            color: white;
            height: 50px;
        }
        .menu-icon {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 20px;
            cursor: pointer;
            margin-right: 20px;
        }
        .menu-icon .line {
            width: 25px;
            height: 3px;
            background-color: white;
            border-radius: 2px;
        }
        .home-section {
            display: flex;
            align-items: center;
        }
        .home-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        .home-text {
            font-size: 22px;
            font-weight: bold;
            color: white;
        }
        .img-home {
            height: 30px;
        }
        .side-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 270px;
            height: 100%;
            background-color: #0033cc;
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            overflow-y: auto;
        }
        .side-menu .close-button {
            background: none;
            border: none;
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            display: block;
            margin-bottom: 20px;
        }
        .side-menu ul {
            list-style: none;
            padding: 0;
        }
        .side-menu ul li {
            margin: 15px 0;
        }
        .side-menu ul li.menu-section-title {
            font-size: 14px;
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 20px;
            color: white;
            border-top: 1px solid white;
            padding-top: 10px;
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
        .side-menu.open {
            display: block;
            animation: slideIn 0.3s forwards;
        }
        header {
           
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .container h2 {
            margin-top: 0;
            color: #333;
        }

        .container {
            width: 90%;
            max-width: 800px;
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
            border-bottom: 5px solid #004494;
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

        table {
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

        .remarks {
            font-weight: bold;
        }

        .remarks.passed {
            color: green;
        }

        .remarks.failed {
            color: red;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <!-- Top Header -->
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Student</span>
                <a href="javascript:void(0);" class="logout-button" onclick="showLogoutModal()">Logout</a>
            </div>
        </div>
        <br>
        <!-- Sub Header -->
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
    </header>

    <!-- Side Menu -->
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="studentHome.php" class="menu-item">Home</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="studentPROFILE.php" class="menu-item">Profile</a></li>
            <li><a href="section_teachers.php" class="menu-item">Schedule</a></li>
            <li><a href="studentGrades.php" class="menu-item">Grades</a></li> <!-- Added link to studentGrades.php -->
            <li class="menu-section-title">ACCOUNTS</li>
            <li><a href="studentPAYMENT.php" class="menu-item">Payment</a></li>
            <li class="menu-section-title">NAVIGATION</li>
            <li><a href="studentMAP.php" class="menu-item">Campus Map</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="header">
            <h1>Student Grades (Tentative)</h1> <!-- Added "Tentative" -->
            <p>Student: <?php echo htmlspecialchars($student['shstud_firstname'] . ' ' . $student['shstud_lastname']); ?></p>
            <!-- Removed Strand -->
        </div>
        <table>
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Field</th> <!-- Added column for Teacher's Field -->
                    <th>1st Grading</th>
                    <th>2nd Grading</th>
                    <th>3rd Grading</th>
                    <th>4th Grading</th>
                    <th>Final Grade</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($grade): ?>
                <?php 
                    $result = calculateFinalGrade([
                        $grade['first_grading'], 
                        $grade['second_grading'], 
                        $grade['third_grading'], 
                        $grade['fourth_grading']
                    ]);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($grade['teacher_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($grade['teacher_field'] ?? 'N/A'); ?></td> <!-- Display Teacher's Field -->
                    <td><?php echo htmlspecialchars($grade['first_grading'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($grade['second_grading'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($grade['third_grading'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($grade['fourth_grading'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars(number_format($result['finalGrade'], 2)); ?></td>
                    <td class="remarks <?php echo $result['remark'] === 'Passed' ? 'passed' : 'failed'; ?>">
                        <?php echo htmlspecialchars($result['remark']); ?>
                    </td>
                </tr>
                <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; font-size: 16px; color: #666;">
                        No grades available.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.history.back();" style="padding: 10px 20px; font-size: 16px; background-color: #0056b3; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Back
        </button>
    </div>
</body>
<script>
    const burgerMenu = document.getElementById('burger-menu');
    const sideMenu = document.getElementById('side-menu');
    const closeMenu = document.getElementById('close-menu');

    // Open the menu when clicking the burger icon
    burgerMenu.addEventListener('click', function () {
        sideMenu.classList.add('open');
    });

    // Close the menu when clicking the close button
    closeMenu.addEventListener('click', function () {
        sideMenu.classList.remove('open');
    });

    function showLogoutModal() {
        window.location.href = "?logout=true"; // Redirect to logout URL
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }
</script>

<div id="logoutModal" class="logout-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; z-index: 1000;">
    <div class="logout-modal-content" style="background: white; padding: 20px; border-radius: 8px; text-align: center; width: 300px;">
        <h3>Are you sure you want to log out?</h3>
        <form action="" method="GET" style="display: inline;">
            <button type="submit" name="logout" value="true" class="confirm-logout" style="background-color: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">Yes</button>
        </form>
        <button class="cancel-logout" onclick="closeLogoutModal()" style="background-color: #ccc; color: black; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">No</button>
    </div>
</div>
</html>