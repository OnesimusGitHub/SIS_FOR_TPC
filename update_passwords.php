<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT loginid, loginpass FROM login";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hashedPassword = password_hash($row['loginpass'], PASSWORD_DEFAULT);
        $updateSql = "UPDATE login SET loginpass = ? WHERE loginid = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param('si', $hashedPassword, $row['loginid']);
        $stmt->execute();
    }
    echo "Passwords updated successfully.";
} else {
    echo "No records found.";
}

$conn->close();
?>
