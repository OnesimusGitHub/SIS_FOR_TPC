<?php
session_start();

// Check if the registrar is logged in
if (!isset($_SESSION['registrar_ID'])) {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo "Database connection failed: " . $conn->connect_error;
    exit();
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $paymentId = isset($data['payment_id']) ? $data['payment_id'] : null;

    if (!$paymentId) {
        http_response_code(400);
        echo "Payment ID is required.";
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM tblpayments WHERE payment_ID = ?");
    $stmt->bind_param("i", $paymentId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "Payment record successfully deleted.";
        } else {
            http_response_code(404);
            echo "Payment record not found.";
        }
    } else {
        http_response_code(500);
        echo "An error occurred while deleting the payment record.";
    }

    $stmt->close();
} else {
    http_response_code(405);
    echo "Invalid request method.";
}

$conn->close();
?>
