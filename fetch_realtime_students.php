<?php
header('Content-Type: application/json');

// Start output buffering to suppress any unexpected output
ob_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Fetch updated grade data
$grades = [];
$query = "
    SELECT 
        tblshsstudent.shsstud_ID, 
        IFNULL(tblshsstudent.student_grade, 'Not Assigned') AS student_grade
    FROM tblshsstudent
";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
}

// Clear any unexpected output and return the JSON response
ob_end_clean();
echo json_encode(['success' => true, 'grades' => $grades]);

$conn->close();
?>
