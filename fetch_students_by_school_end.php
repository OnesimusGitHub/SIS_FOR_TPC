<?php
header('Content-Type: application/json');

// Suppress unexpected output
ob_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    ob_end_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Get the current date
$currentDate = date('Y-m-d');

// Fetch students where school_end has already passed
$sql = "SELECT shsstud_ID, shstud_firstname, shstud_lastname, school_year, school_end 
        FROM tblshsstudent 
        WHERE school_end < ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    ob_end_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s", $currentDate);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Debugging: Check if the query executed correctly
if ($conn->error) {
    ob_end_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    exit;
}

// Return the result as JSON
ob_end_clean(); // Clear any unexpected output
echo json_encode([
    'success' => true,
    'students' => $students
]);

$stmt->close();
$conn->close();
?>
