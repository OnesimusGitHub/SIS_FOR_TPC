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

$sql = "SELECT announce_content, type, image_path, DATE_FORMAT(announcement_ct, '%M %d, %Y %h:%i %p') AS announcement_ct FROM tblannouncementinfo ORDER BY announce_ID DESC";
$result = $conn->query($sql);

$announcements = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($announcements);
?>
