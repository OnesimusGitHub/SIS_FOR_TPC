<?php
session_start();

// Check if the registrar is logged in
if (!isset($_SESSION['registrar_ID'])) {
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
    die("Connection failed: " . $conn->connect_error);
}

// Handle payment submission for all students
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['reason'] ?? '';
    $customReason = $_POST['custom_reason'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $dueDate = $_POST['due_date'] ?? '';

    if ($reason === 'More' && !empty($customReason)) {
        $reason = $customReason;
    }

    // Fetch all student IDs
    $result = $conn->query("SELECT shsstud_ID FROM tblshsstudent");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $studentId = $row['shsstud_ID'];

            // Insert payment record for each student
            $stmt = $conn->prepare("INSERT INTO tblpayments (student_id, reason, amount, due_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $studentId, $reason, $amount, $dueDate);
            $stmt->execute();
        }
        echo "Payment details submitted for all students.";
    } else {
        echo "No students found.";
    }
    $conn->close();
}
