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

// Fetch updated section data
$sections = [];
$query = "
    SELECT 
        tblshsstudent.shsstud_ID, 
        tblshsstudent.student_grade, 
        tblshsstudent.strand_ID, 
        IFNULL(tblshssection.section_Name, 'Not Assigned') AS section_name
    FROM tblshsstudent
    LEFT JOIN tblshssection 
        ON tblshsstudent.section_ID = tblshssection.section_ID
        AND tblshsstudent.student_grade = tblshssection.shsgrade
        AND tblshsstudent.strand_ID = tblshssection.strand_ID
";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
}

// Clear any unexpected output and return the JSON response
ob_end_clean();
echo json_encode(['success' => true, 'sections' => $sections]);

$conn->close();
?>
