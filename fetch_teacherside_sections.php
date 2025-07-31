<?php
header('Content-Type: application/json');
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Check if the teacher is logged in
if (!isset($_SESSION["teacherid"]) || empty($_SESSION["teacherid"])) {
    echo json_encode(['success' => false, 'message' => 'Teacher not logged in']);
    exit;
}

$teacherID = $_SESSION["teacherid"]; // Get teacher ID from session

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Debugging: Log the teacher ID
    error_log("Teacher ID: " . $teacherID);

    // Fetch sections assigned to the teacher
    $sqlSections = "
        SELECT s.section_ID, s.section_Name
        FROM tblshssection s
        INNER JOIN tblsecteacher st ON s.section_ID = st.section_ID
        WHERE st.teacher_ID = :teacherID
        ORDER BY s.section_Name ASC
    ";
    $stmtSections = $pdo->prepare($sqlSections);
    $stmtSections->bindParam(':teacherID', $teacherID, PDO::PARAM_INT);
    $stmtSections->execute();
    $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Log the fetched sections
    error_log("Fetched Sections: " . json_encode($sections));

    $sectionsWithStudents = [];

    // Fetch students for each section
    foreach ($sections as $section) {
        $sqlStudents = "
            SELECT 
                tss.shsstud_ID, 
                tss.shstud_firstname AS first_name, 
                tss.shstud_lastname AS last_name, 
                COALESCE(ts.strand_code, 'No Strand') AS strand_code, 
                IFNULL(tss.student_grade, 'Not Assigned') AS grade
            FROM tblshsstudent tss
            LEFT JOIN tblstrand ts ON tss.strand_ID = ts.strand_ID
            WHERE tss.section_ID = :section_ID
            ORDER BY tss.shstud_lastname ASC, tss.shstud_firstname ASC
        ";
        $stmtStudents = $pdo->prepare($sqlStudents);
        $stmtStudents->bindParam(':section_ID', $section['section_ID'], PDO::PARAM_INT);
        $stmtStudents->execute();
        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

        $sectionsWithStudents[] = [
            'section_ID' => $section['section_ID'],
            'section_Name' => $section['section_Name'],
            'students' => $students
        ];
    }

    echo json_encode(['success' => true, 'sections' => $sectionsWithStudents]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
