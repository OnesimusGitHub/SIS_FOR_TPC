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

// Delete all payment records
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM tblpayments");
    if ($stmt->execute()) {
        echo "All payment records have been successfully deleted.";
    } else {
        http_response_code(500);
        echo "An error occurred while deleting payment records.";
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo "Invalid request method.";
}

$conn->close();
?>
