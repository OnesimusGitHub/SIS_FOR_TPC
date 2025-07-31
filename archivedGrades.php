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

    // Handle restore request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_grade'])) {
        $studentID = $_POST['restore_grade'];
        $sqlRestore = "UPDATE tblgrades SET archived = NULL WHERE shsstud_ID = :shsstud_ID";
        $stmtRestore = $pdo->prepare($sqlRestore);
        $stmtRestore->execute([':shsstud_ID' => $studentID]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Filter logic
    $searchQuery = '';
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
        $searchQuery = trim($_GET['search']);
        $sql = "SELECT g.shsstud_ID, g.first_grading, g.second_grading, g.third_grading, g.fourth_grading, 
                       g.teacher_name, g.teacher_field, s.shstud_firstname, s.shstud_lastname, sec.section_name, 
                       t.grade
                FROM tblgrades g
                INNER JOIN tblshsstudent s ON g.shsstud_ID = s.shsstud_ID
                INNER JOIN teachrinf t ON g.teacher_name = t.teachername
                INNER JOIN tblshssection sec ON s.section_ID = sec.section_ID
                WHERE g.archived = 1
                  AND (s.shstud_firstname LIKE :search OR s.shstud_lastname LIKE :search OR sec.section_name LIKE :search)
                ORDER BY s.shstud_lastname ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':search' => "%$searchQuery%"]);
        $archivedGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT g.shsstud_ID, g.first_grading, g.second_grading, g.third_grading, g.fourth_grading, 
                       g.teacher_name, g.teacher_field, s.shstud_firstname, s.shstud_lastname, sec.section_name, 
                       t.grade
                FROM tblgrades g
                INNER JOIN tblshsstudent s ON g.shsstud_ID = s.shsstud_ID
                INNER JOIN teachrinf t ON g.teacher_name = t.teachername
                INNER JOIN tblshssection sec ON s.section_ID = sec.section_ID
                WHERE g.archived = 1
                ORDER BY s.shstud_lastname ASC";
        $stmt = $pdo->query($sql);
        $archivedGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


} catch (PDOException $e) {
    die("<h1>ERROR: Could not connect. " . htmlspecialchars($e->getMessage()) . "</h1>");
}

unset($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Grades</title>
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
            background-color: #343a40;
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 12px 12px 0 0;
            position: relative;
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
        .header a {
            position: absolute;
            top: 25px;
            right: 25px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .header a:hover {
            background-color: #0056b3;
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
            background-color: #343a40;
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
        .no-data {
            text-align: center;
            font-size: 18px;
            color: #666;
            padding: 20px;
        }
        .restore-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .restore-button:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        /* Search bar styling */
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
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .search-bar input[type="text"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            outline: none;
        }
        @media (max-width: 768px) {
            .grades-table th, .grades-table td {
                font-size: 12px;
                padding: 10px;
            }
            .restore-button {
                padding: 8px 12px;
                font-size: 12px;
            }
            .search-bar input[type="text"] {
                width: 90%;
            }
        }
    </style>
    <script>
        function searchGrades() {
            const searchInput = document.getElementById('searchInput').value;
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `archivedGrades.php?search=${encodeURIComponent(searchInput)}`, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(xhr.responseText, 'text/html');
                    const newTableBody = doc.querySelector('tbody').innerHTML;
                    document.querySelector('tbody').innerHTML = newTableBody;
                }
            };
            xhr.send();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Archived Grades</h1>
            <p>View all archived grades</p>
            <a href="javascript:history.back()">Back</a>
        </div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search by name or section" onkeyup="searchGrades()" value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        <form method="POST">
            <table class="grades-table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Grade</th>
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
                    <?php if (!empty($archivedGrades)): ?>
                        <?php foreach ($archivedGrades as $grade): ?>
                            <?php 
                                // Filter out invalid or empty grades
                                $gradeValues = array_filter([
                                    $grade['first_grading'], 
                                    $grade['second_grading'], 
                                    $grade['third_grading'], 
                                    $grade['fourth_grading']
                                ], fn($value) => is_numeric($value) && $value !== null);

                                // Calculate the final grade only if there are valid grades
                                $finalGrade = !empty($gradeValues) ? array_sum($gradeValues) / count($gradeValues) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['teacher_name']); ?></td>
                                <td><?php echo htmlspecialchars($grade['teacher_field'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($grade['section_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($grade['grade'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($grade['shstud_firstname']); ?></td>
                                <td><?php echo htmlspecialchars($grade['shstud_lastname']); ?></td>
                                <td><?php echo htmlspecialchars($grade['first_grading']); ?></td>
                                <td><?php echo htmlspecialchars($grade['second_grading']); ?></td>
                                <td><?php echo htmlspecialchars($grade['third_grading']); ?></td>
                                <td><?php echo htmlspecialchars($grade['fourth_grading']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($finalGrade, 2)); ?></td>
                                <td>
                                    <button type="submit" name="restore_grade" value="<?php echo $grade['shsstud_ID']; ?>" class="restore-button">Restore</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="no-data">No results found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</body>
</html>
