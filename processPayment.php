<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $reason = $_POST['reason'];
    $custom_reason = isset($_POST['custom_reason']) ? $_POST['custom_reason'] : null;
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];

    // Insert payment details into the database
    $stmt = $conn->prepare("INSERT INTO tblpayments (student_id, reason, custom_reason, amount, due_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $student_id, $reason, $custom_reason, $amount, $due_date);

    if ($stmt->execute()) {
        echo "Payment details successfully recorded.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
