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

// Fetch updated strand and section data
$strandSectionData = [];
$query = "
    SELECT 
        tblshsstudent.shsstud_ID, 
        tblstrand.strand_code AS strand_code, 
        tblshssection.section_Name AS section_name
    FROM tblshsstudent
    LEFT JOIN tblstrand ON tblshsstudent.strand_ID = tblstrand.strand_ID
    LEFT JOIN tblshssection ON tblshsstudent.section_ID = tblshssection.section_ID
";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $strandSectionData[] = $row;
    }
}

// Clear any unexpected output and return the JSON response
ob_end_clean();
echo json_encode(['success' => true, 'data' => $strandSectionData]);

$conn->close();
?>
