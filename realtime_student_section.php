<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

$studentId = $_GET['studentid'] ?? null;

if (!$studentId) {
    echo json_encode(['error' => 'Student ID is required.']);
    exit;
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("
        SELECT s.section_ID, s.section_Name 
        FROM tblshssection s
        INNER JOIN tblshsstudent st ON s.strand_ID = st.strand_ID AND s.shsgrade = st.student_grade
        WHERE st.shsstud_ID = ?
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }

    echo json_encode(['sections' => $sections]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}
