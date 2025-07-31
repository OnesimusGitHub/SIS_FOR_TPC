<?php
session_start();

if (!isset($_SESSION["teacherid"])) {
    header("Location: teacherLogin.php");
    exit;
}

// Define database connection variables
$database = "sis";
$username = "root";
$password = "";

// Retrieve section_ID from GET or set a default value
$section_ID = $_GET['section_ID'] ?? null;

if (!$section_ID || !is_numeric($section_ID)) {
    die("<h1>Invalid or missing section ID. <a href='javascript:history.back()'>Go Back</a></h1>");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch students' details in the section
    $sql = "SELECT s.shsstud_ID, s.shstud_firstname, s.shstud_lastname, s.strand_ID, str.strand_name
            FROM tblshsstudent s
            INNER JOIN tblstrand str ON s.strand_ID = str.strand_ID
            WHERE s.section_ID = :section_ID";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':section_ID', $section_ID, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        die("<h1>No students found for this section. <a href='javascript:history.back()'>Go Back</a></h1>");
    }

    // Fetch teacher and strand information
    $sqlTeacher = "SELECT teachername, teacherfield, str.strand_name AS strand_name
                   FROM teachrinf t
                   INNER JOIN tblstrand str ON t.strand_ID = str.strand_ID
                   WHERE t.teacherid = :teacherid";
    $stmtTeacher = $pdo->prepare($sqlTeacher);
    $stmtTeacher->bindParam(':teacherid', $_SESSION["teacherid"], PDO::PARAM_INT);
    $stmtTeacher->execute();
    $teacherInfo = $stmtTeacher->fetch(PDO::FETCH_ASSOC);

    // Fetch students' grades
    $sqlGrades = "SELECT shsstud_ID, first_grading, second_grading, third_grading, fourth_grading 
                  FROM tblgrades 
                  WHERE section_ID = :section_ID";
    $stmtGrades = $pdo->prepare($sqlGrades);
    $stmtGrades->bindParam(':section_ID', $section_ID, PDO::PARAM_INT);
    $stmtGrades->execute();
    $gradesList = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

    // Map grades to students
    $studentGrades = [];
    foreach ($gradesList as $grade) {
        $studentGrades[$grade['shsstud_ID']] = [
            $grade['first_grading'] ?? 0,
            $grade['second_grading'] ?? 0,
            $grade['third_grading'] ?? 0,
            $grade['fourth_grading'] ?? 0
        ];
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

            // Check if grades already exist for the student
            $sqlCheck = "SELECT COUNT(*) AS count FROM tblgrades WHERE shsstud_ID = :shsstud_ID AND section_ID = :section_ID";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([
                ':shsstud_ID' => $studentID,
                ':section_ID' => $section_ID
            ]);
            $exists = $stmtCheck->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if ($exists) {
                // Update existing grades
                $sqlUpdate = "UPDATE tblgrades 
                              SET first_grading = :first_grading, 
                                  second_grading = :second_grading, 
                                  third_grading = :third_grading, 
                                  fourth_grading = :fourth_grading, 
                                  teacher_name = :teacher_name, 
                                  teacher_field = :teacher_field, 
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE shsstud_ID = :shsstud_ID AND section_ID = :section_ID";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':shsstud_ID' => $studentID,
                    ':section_ID' => $section_ID,
                    ':first_grading' => $grades['first'],
                    ':second_grading' => $grades['second'],
                    ':third_grading' => $grades['third'],
                    ':fourth_grading' => $grades['fourth'],
                    ':teacher_name' => $teacherInfo['teachername'],
                    ':teacher_field' => $teacherInfo['teacherfield']
                ]);
            } else {
                // Insert new grades
                $sqlInsert = "INSERT INTO tblgrades (shsstud_ID, section_ID, first_grading, second_grading, third_grading, fourth_grading, teacher_name, teacher_field, updated_at)
                              VALUES (:shsstud_ID, :section_ID, :first_grading, :second_grading, :third_grading, :fourth_grading, :teacher_name, :teacher_field, CURRENT_TIMESTAMP)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':shsstud_ID' => $studentID,
                    ':section_ID' => $section_ID,
                    ':first_grading' => $grades['first'],
                    ':second_grading' => $grades['second'],
                    ':third_grading' => $grades['third'],
                    ':fourth_grading' => $grades['fourth'],
                    ':teacher_name' => $teacherInfo['teachername'],
                    ':teacher_field' => $teacherInfo['teacherfield']
                ]);
            }
        } catch (PDOException $e) {
            die("<h1>ERROR: Could not save grades. " . htmlspecialchars($e->getMessage()) . "</h1>");
        }
        unset($pdo);
        header("Location: " . $_SERVER['PHP_SELF'] . "?section_ID=" . $section_ID);
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

                // Check if grades already exist for the student
                $sqlCheck = "SELECT COUNT(*) AS count FROM tblgrades WHERE shsstud_ID = :shsstud_ID AND section_ID = :section_ID";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute([
                    ':shsstud_ID' => $studentID,
                    ':section_ID' => $section_ID
                ]);
                $exists = $stmtCheck->fetch(PDO::FETCH_ASSOC)['count'] > 0;

                if ($exists) {
                    // Update existing grades
                    $sqlUpdate = "UPDATE tblgrades 
                                  SET first_grading = :first_grading, 
                                      second_grading = :second_grading, 
                                      third_grading = :third_grading, 
                                      fourth_grading = :fourth_grading, 
                                      teacher_name = :teacher_name, 
                                      teacher_field = :teacher_field, 
                                      updated_at = CURRENT_TIMESTAMP
                                  WHERE shsstud_ID = :shsstud_ID AND section_ID = :section_ID";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->execute([
                        ':shsstud_ID' => $studentID,
                        ':section_ID' => $section_ID,
                        ':first_grading' => $grades['first'],
                        ':second_grading' => $grades['second'],
                        ':third_grading' => $grades['third'],
                        ':fourth_grading' => $grades['fourth'],
                        ':teacher_name' => $teacherInfo['teachername'],
                        ':teacher_field' => $teacherInfo['teacherfield']
                    ]);
                } else {
                    // Insert new grades
                    $sqlInsert = "INSERT INTO tblgrades (shsstud_ID, section_ID, first_grading, second_grading, third_grading, fourth_grading, teacher_name, teacher_field, updated_at)
                                  VALUES (:shsstud_ID, :section_ID, :first_grading, :second_grading, :third_grading, :fourth_grading, :teacher_name, :teacher_field, CURRENT_TIMESTAMP)";
                    $stmtInsert = $pdo->prepare($sqlInsert);
                    $stmtInsert->execute([
                        ':shsstud_ID' => $studentID,
                        ':section_ID' => $section_ID,
                        ':first_grading' => $grades['first'],
                        ':second_grading' => $grades['second'],
                        ':third_grading' => $grades['third'],
                        ':fourth_grading' => $grades['fourth'],
                        ':teacher_name' => $teacherInfo['teachername'],
                        ':teacher_field' => $teacherInfo['teacherfield']
                    ]);
                }
            }
        } catch (PDOException $e) {
            die("<h1>ERROR: Could not save grades. " . htmlspecialchars($e->getMessage()) . "</h1>");
        }
        unset($pdo);
        header("Location: " . $_SERVER['PHP_SELF'] . "?section_ID=" . $section_ID);
        exit;
    } else {
        die("<h1>Invalid form submission. Please ensure all fields are filled out correctly.</h1>");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_to_admin'])) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Mark grades as submitted by updating the `updated_at` field
        $sqlSubmit = "UPDATE tblgrades 
                      SET updated_at = CURRENT_TIMESTAMP 
                      WHERE section_ID = :section_ID AND teacher_name = :teacher_name";
        $stmtSubmit = $pdo->prepare($sqlSubmit);
        $stmtSubmit->execute([
            ':section_ID' => $section_ID,
            ':teacher_name' => $teacherInfo['teachername']
        ]);
    } catch (PDOException $e) {
        die("<h1>ERROR: Could not submit grades to admin. " . htmlspecialchars($e->getMessage()) . "</h1>");
    }
    unset($pdo);
    header("Location: " . $_SERVER['PHP_SELF'] . "?section_ID=" . $section_ID . "&submitted=1");
    exit;
}

// Check if grades have already been submitted to admin
$gradesSubmitted = false;
try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if grades exist for the section and teacher
    $sqlCheckSubmission = "SELECT COUNT(*) AS count 
                           FROM tblgrades 
                           WHERE section_ID = :section_ID AND teacher_name = :teacher_name";
    $stmtCheckSubmission = $pdo->prepare($sqlCheckSubmission);
    $stmtCheckSubmission->execute([
        ':section_ID' => $section_ID,
        ':teacher_name' => $teacherInfo['teachername']
    ]);
    $result = $stmtCheckSubmission->fetch(PDO::FETCH_ASSOC);
    $gradesSubmitted = $result['count'] > 0;
} catch (PDOException $e) {
    die("<h1>ERROR: Could not check submission status. " . htmlspecialchars($e->getMessage()) . "</h1>");
}
unset($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class List</title>
    <style>
        /* General Styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 20px;
        }
        .header {
            background: linear-gradient(to right, #0056b3, #007bff);
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
        .back-button {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
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
        .save-button {
            display: inline-block;
            margin: 5px 0;
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .save-button:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .save-all-button {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #17a2b8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .save-all-button:hover {
            background-color: #138496;
            transform: scale(1.05);
        }
        .submit-button {
            background-color: #ffc107;
            color: black;
        }
        .submit-button:hover {
            background-color: #e0a800;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Class List for Section: <?php echo htmlspecialchars($section_ID); ?></h1>
            <p>Teacher: <?php echo htmlspecialchars($teacherInfo['teachername'] ?? 'N/A'); ?></p>
            <p>Field: <?php echo htmlspecialchars($teacherInfo['teacherfield'] ?? 'N/A'); ?></p>
            <p>Strand: <?php echo htmlspecialchars($teacherInfo['strand_name'] ?? 'N/A'); ?></p>
        </div>
        <a href="view_class_list.php?section_ID=<?php echo htmlspecialchars($section_ID); ?>" class="back-button">Go Back</a>
        <?php if (!empty($students)): ?>
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Strand</th>
                            <th>1st Grading</th>
                            <th>2nd Grading</th>
                            <th>3rd Grading</th>
                            <th>4th Grading</th>
                            <th>Final Grade</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php 
                                $grades = $studentGrades[$student['shsstud_ID']] ?? [0, 0, 0, 0];
                                $result = calculateFinalGrade($grades); // Calculate final grade and remarks
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['shsstud_ID']); ?></td>
                                <td><?php echo htmlspecialchars($student['shstud_firstname']); ?></td>
                                <td><?php echo htmlspecialchars($student['shstud_lastname']); ?></td>
                                <td><?php echo htmlspecialchars($student['strand_name']); ?></td> <!-- Updated to display strand name -->
                                <td><input type="number" name="grades[<?php echo $student['shsstud_ID']; ?>][first]" value="<?php echo htmlspecialchars($grades[0] ?? 0); ?>" step="0.01" required></td>
                                <td><input type="number" name="grades[<?php echo $student['shsstud_ID']; ?>][second]" value="<?php echo htmlspecialchars($grades[1] ?? 0); ?>" step="0.01" required></td>
                                <td><input type="number" name="grades[<?php echo $student['shsstud_ID']; ?>][third]" value="<?php echo htmlspecialchars($grades[2] ?? 0); ?>" step="0.01" required></td>
                                <td><input type="number" name="grades[<?php echo $student['shsstud_ID']; ?>][fourth]" value="<?php echo htmlspecialchars($grades[3] ?? 0); ?>" step="0.01" required></td>
                                <td><?php echo htmlspecialchars(number_format($result['finalGrade'], 2)); ?></td>
                                <td class="remarks <?php echo $result['remark'] === 'Passed' ? 'passed' : 'failed'; ?>">
                                    <?php echo htmlspecialchars($result['remark']); ?>
                                </td>
                                <td>
                                    <button type="submit" name="save_grade" value="<?php echo $student['shsstud_ID']; ?>" class="save-button">Save Grade</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="save_all_grades" class="save-all-button">Save Grades for Everyone</button>
                <button type="submit" name="submit_to_admin" class="save-all-button submit-button">Submit Grades to Admin</button>
            </form>
        <?php else: ?>
            <div class="no-data">No students found for this section.</div>
        <?php endif; ?>
    </div>
</body>
</html>