<?php
session_start();

if (!isset($_SESSION["teacherid"])) {
    header("Location: teacherLogin.php");
    exit;
}

$section_ID = $_GET['section_ID'] ?? null;

if (!$section_ID) {
    die("Invalid section ID.");
}

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch teacher's field
    $teacherField = null;
    $sqlTeacher = "SELECT teacherfield FROM teachrinf WHERE teacherid = :teacherid";
    $stmtTeacher = $pdo->prepare($sqlTeacher);
    $stmtTeacher->bindParam(':teacherid', $_SESSION["teacherid"], PDO::PARAM_INT);
    $stmtTeacher->execute();
    $teacherField = $stmtTeacher->fetchColumn();

    // Fetch students in the section
    $sql = "SELECT shsstud_ID, shstud_firstname, shstud_lastname, strand_ID, student_grade 
            FROM tblshsstudent 
            WHERE section_ID = :section_ID";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':section_ID', $section_ID, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
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
        /* General Body Styling */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
            color: #333;
        }

        /* Container Styling */
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

        /* Header Styling */
        .header {
            background-color: #0056b3;
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            border-bottom: 5px solid #004494;
            border-radius: 12px 12px 0 0;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }

        .header .date {
            font-size: 16px;
            margin-top: 10px;
            color: #cce0ff;
        }

        .back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .back-button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .grades-button {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .grades-button:hover {
            background-color: #218838;
            transform: scale(1.05);
        }

        /* Table Styling */
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

        /* Footer Styling */
        .footer {
            background-color: #0056b3;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 14px;
            border-top: 5px solid #004494;
            border-radius: 0 0 12px 12px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 22px;
            }

            .back-button {
                font-size: 12px;
                padding: 8px 15px;
            }

            th, td {
                font-size: 12px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Class : <?php echo htmlspecialchars($section_ID); ?> - <?php echo htmlspecialchars($teacherField ?? 'N/A'); ?></h1>
            <div class="date">Date: <?php echo date("l, F j, Y"); ?></div>
            <button onclick="window.location.href='teacher_dashboard.php'" class="back-button">Back</button>
            <a href="teacherGrades.php?section_ID=<?php echo urlencode($section_ID); ?>" class="grades-button">View Grades</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Strand</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['shsstud_ID']); ?></td>
                        <td><?php echo htmlspecialchars($student['shstud_firstname']); ?></td>
                        <td><?php echo htmlspecialchars($student['shstud_lastname']); ?></td>
                        <td><?php echo htmlspecialchars($student['strand_ID']); ?></td>
                        <td><?php echo htmlspecialchars($student['student_grade']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="footer">
            &copy; <?php echo date("Y"); ?> TPC School Information System
        </div>
    </div>
</body>
</html>
