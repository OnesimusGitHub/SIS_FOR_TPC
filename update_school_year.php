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

if (!isset($input['studentId'], $input['schoolYear'], $input['schoolEnd'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$studentId = $input['studentId'];
$schoolYear = $input['schoolYear'];
$schoolEnd = $input['schoolEnd'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Update the student's school_year and school_end
$sql = "UPDATE tblshsstudent SET school_year = ?, school_end = ? WHERE shsstud_ID = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sss", $schoolYear, $schoolEnd, $studentId);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'School year updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update school year: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
