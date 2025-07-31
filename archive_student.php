<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['studentId'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
    exit;
}

$studentId = $input['studentId'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Update the student's studstat to ARCHIVED
$sql = "UPDATE tblshsstudent SET studstat = 'ARCHIVED' WHERE shsstud_ID = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s", $studentId);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student archived successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to archive student: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
