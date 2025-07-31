<?php
session_start();

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

    // Fetch teacherid directly from the login table
    $sqlTeacherId = "SELECT teacherid FROM login WHERE loginid = :loginid";
    $stmtTeacherId = $pdo->prepare($sqlTeacherId);
    $stmtTeacherId->bindParam(':loginid', $_SESSION["loginid"], PDO::PARAM_INT);
    $stmtTeacherId->execute();
    $teacher = $stmtTeacherId->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        http_response_code(404);
        echo json_encode(["error" => "Teacher not found for the given loginid."]);
        exit;
    }

    $teacherId = (int) $teacher['teacherid'];

    // Fetch teacherfield from teachrinf table
    $sqlTeacherField = "SELECT teacherfield FROM teachrinf WHERE teacherid = :teacherid";
    $stmtTeacherField = $pdo->prepare($sqlTeacherField);
    $stmtTeacherField->bindParam(':teacherid', $teacherId, PDO::PARAM_INT);
    $stmtTeacherField->execute();
    $teacherField = $stmtTeacherField->fetch(PDO::FETCH_ASSOC);

    if (!$teacherField) {
        http_response_code(404);
        echo json_encode(["error" => "Teacher field not found for the given teacherid."]);
        exit;
    }

    echo json_encode(["teacherfield" => $teacherField['teacherfield']]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
