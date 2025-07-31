<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Fetch students without a section
$studentsWithoutSection = [];
$query = "
    SELECT 
        tblshsstudent.shsstud_ID, 
        tblshsstudent.shstud_firstname, 
        tblshsstudent.shstud_lastname, 
        tblshssection.section_Name, 
        tblstrand.strand_code
    FROM tblshsstudent
    LEFT JOIN tblshssection ON tblshsstudent.section_ID = tblshssection.section_ID
    LEFT JOIN tblstrand ON tblshsstudent.strand_ID = tblstrand.strand_ID
    WHERE tblshsstudent.section_ID IS NULL OR tblshsstudent.section_ID = ''
";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $studentsWithoutSection[] = $row;
    }
}

// Return the data as JSON
echo json_encode(['success' => true, 'students' => $studentsWithoutSection]);

$conn->close();
?>
