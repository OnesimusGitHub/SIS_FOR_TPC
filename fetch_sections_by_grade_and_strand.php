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

// Get grade and strand from the request
$grade = $_GET['grade'] ?? null;
$strand = $_GET['strand'] ?? null;

if (!$grade || !$strand) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Missing grade or strand']);
    exit;
}

// Fetch sections based on grade and strand
$sections = [];
$query = "
    SELECT section_ID, section_Name 
    FROM tblshssection 
    WHERE shsgrade = ? AND strand_ID = (
        SELECT strand_ID FROM tblstrand WHERE strand_code = ?
    )
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $grade, $strand);
$stmt->execute();
$result = $stmt->get_result();

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
