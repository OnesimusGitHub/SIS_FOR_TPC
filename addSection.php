<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $strand_ID = $_POST['strand_ID'] ?? '';
    $section_name = $_POST['section_name'] ?? '';

    if (!empty($strand_ID) && !empty($section_name)) {
        $stmt = $conn->prepare("INSERT INTO tblshssection (strand_ID, section_name) VALUES (?, ?)");
        $stmt->bind_param("is", $strand_ID, $section_name);

        if ($stmt->execute()) {
            echo "<script>alert('Section added successfully!'); window.location.href = 'admin_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error adding section: " . $stmt->error . "'); window.location.href = 'admin_dashboard.php';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Please fill in all fields.'); window.location.href = 'admin_dashboard.php';</script>";
    }
}

$conn->close();
?>
