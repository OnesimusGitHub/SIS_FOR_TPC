<?php
require 'db_connection.php'; // Include your database connection

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['teacherid'])) {
    $teacherid = $data['teacherid'];

    try {
        $sql = "UPDATE teachrinf SET teachstat = NULL WHERE teacherid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $teacherid);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Teacher restored successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to restore teacher.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
