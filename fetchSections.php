<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $grade = $_GET['grade'] ?? '';
    $strand = $_GET['strand'] ?? '';
    $sections = [];

    if ($grade && $strand) {
        $stmt = $conn->prepare("
            SELECT section_ID, section_Name 
            FROM tblshssection 
            WHERE shsgrade = ? AND strand_ID = ?
        ");
        $stmt->bind_param("si", $grade, $strand); // Bind grade as string and strand as integer
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }

        $stmt->close();
    }

    echo json_encode($sections);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>