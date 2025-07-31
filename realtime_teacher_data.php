<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["loginid"])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful."); // Debugging: Log successful connection
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}

$data = [
    "teacherfield" => "N/A",
    "sections" => []
];

try {
    // Fetch teacherid directly from the login table
    error_log("Fetching teacherid for loginid: " . $_SESSION["loginid"]); // Debugging: Log loginid
    $sqlTeacherId = "SELECT teacherid FROM login WHERE loginid = :loginid";
    $stmtTeacherId = $pdo->prepare($sqlTeacherId);
    $stmtTeacherId->bindParam(':loginid', $_SESSION["loginid"], PDO::PARAM_INT);
    $stmtTeacherId->execute();
    $teacher = $stmtTeacherId->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        error_log("No teacher found for loginid: " . $_SESSION["loginid"]); // Debugging: Log missing teacher
        http_response_code(404);
        echo json_encode(["error" => "Teacher not found for the given loginid."]);
        exit;
    }

    $teacherId = (int) $teacher['teacherid']; // Ensure teacherid is treated as an integer
    error_log("Teacher ID fetched: " . $teacherId); // Debugging: Log teacherid

    // Fetch teacherfield from teachrinf table
    error_log("Fetching teacherfield for teacherid: " . $teacherId); // Debugging: Log teacherid for teacherfield
    $sqlTeacherField = "SELECT teacherfield FROM teachrinf WHERE teacherid = :teacherid";
    $stmtTeacherField = $pdo->prepare($sqlTeacherField);
    $stmtTeacherField->bindParam(':teacherid', $teacherId, PDO::PARAM_INT);
    $stmtTeacherField->execute();
    $teacherField = $stmtTeacherField->fetch(PDO::FETCH_ASSOC);

    if ($teacherField) {
        $data["teacherfield"] = $teacherField['teacherfield'];
    }

    // Fetch sections assigned to the teacher along with the number of students
    error_log("Fetching sections for teacherid: " . $teacherId); // Debugging: Log teacherid for sections
    $sqlSections = "SELECT sec.section_ID, sec.section_name, 
                           (SELECT COUNT(*) FROM tblshsstudent st WHERE st.section_ID = sec.section_ID) AS no_of_students
                    FROM tblsecteacher st
                    INNER JOIN tblshssection sec ON st.section_ID = sec.section_ID
                    WHERE st.teacher_ID = :teacherid
                    GROUP BY sec.section_ID, sec.section_name";
    $stmtSections = $pdo->prepare($sqlSections);
    $stmtSections->bindParam(':teacherid', $teacherId, PDO::PARAM_INT); // Bind as integer
    $stmtSections->execute();
    $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);

    // Add sections to the response
    foreach ($sections as $row) {
        $data["sections"][] = [
            "section_ID" => $row['section_ID'],
            "section_name" => $row['section_name'],
            "no_of_students" => $row['no_of_students']
        ];
    }
    error_log("Sections fetched successfully."); // Debugging: Log successful fetch
    unset($stmtSections);
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage()); // Debugging: Log query failure
    http_response_code(500);
    echo json_encode(["error" => "Query failed: " . $e->getMessage()]);
    exit;
}

unset($pdo);

header('Content-Type: application/json');
echo json_encode($data);
