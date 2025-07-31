<?php
header('Content-Type: application/json');

if (!isset($_GET['studentid'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
    exit;
}

$studentId = $_GET['studentid'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$sql = "SELECT school_year, school_end FROM tblshsstudent WHERE shsstud_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'school_year' => $row['school_year'],
        'school_end' => $row['school_end']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data found for the given student ID.']);
}

$stmt->close();
$conn->close();
?>
