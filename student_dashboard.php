<?php
session_start();
require 'db_connection.php'; // Ensure this file contains the database connection logic

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit();
}

$studentId = $_SESSION['student_id'];
$sql = "SELECT shstud_email, shstud_firstname, shstud_lastname, shstud_contactno FROM tblshsstudent WHERE shsstud_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$stmt->bind_result($email, $firstName, $lastName, $contactNumber);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword === $confirmPassword) {
        $updateSql = "UPDATE tblshslogin SET shslogin_password = ? WHERE shsstud_ID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('ss', $newPassword, $studentId);

        if ($updateStmt->execute()) {
            $success = "Password updated successfully.";
        } else {
            $error = "Failed to update password.";
        }
        $updateStmt->close();
    } else {
        $error = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h2>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($contactNumber); ?></p>

    <h3>Change Password</h3>
    <?php if (isset($success)) echo "<p style='color: green;'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    <form method="POST">
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required>
        <br>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <br>
        <button type="submit">Update Password</button>
    </form>
    <br>
    <a href="student_logout.php">Logout</a>
</body>
</html>
