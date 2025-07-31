<?php
require 'db_connection.php';

$success = false; // Flag to track if the password reset was successful
$redirectUrl = ''; // URL to redirect after success

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token']) && isset($_GET['account_type'])) {
    $token = $_GET['token'];
    $accountType = $_GET['account_type'];

    // Validate the token
    $sql = "SELECT user_id, expiry FROM password_resets WHERE token = ? AND account_type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $token, $accountType);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $expiry);
        $stmt->fetch();

        if (time() > strtotime($expiry)) {
            die("The reset link has expired.");
        }
    } else {
        die("Invalid reset link.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $accountType = $_POST['account_type'];
    $newPassword = $_POST['password'];
    $repeatPassword = $_POST['repeat_password'];

    // Check if passwords match
    if ($newPassword !== $repeatPassword) {
        die("Passwords do not match.");
    }

    // Validate the token again
    $sql = "SELECT user_id FROM password_resets WHERE token = ? AND account_type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $token, $accountType);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId);
        $stmt->fetch();

        // Determine the table and ID column based on the account type
        $table = '';
        $idColumn = '';
        $passwordColumn = ''; // Add a variable for the password column
        switch ($accountType) {
            case 'student':
                $table = 'tblshslogin';
                $idColumn = 'shslogin_ID';
                $passwordColumn = 'shslogin_password';
                $redirectUrl = 'studentLogin.php';
                break;
            case 'teacher':
                $table = 'login';
                $idColumn = 'loginid';
                $passwordColumn = 'loginpass'; // Correct column name for teacher's password
                $redirectUrl = 'teacherLogin.php';
                break;
            case 'registrar':
                $table = 'tblregistrarlogin';
                $idColumn = 'registrarlogin_ID';
                $passwordColumn = 'registrarlogin_password';
                $redirectUrl = 'registrarLogin.php';
                break;
            case 'admin':
                $table = 'tbladminlogin';
                $idColumn = 'adminlogin_ID';
                $passwordColumn = 'adminlogin_password';
                $redirectUrl = 'adminLogin.php';
                break;
            default:
                die('Invalid account type.');
        }

        // Update the password in plain text
        $updatePassword = $conn->prepare("UPDATE $table SET $passwordColumn = ? WHERE $idColumn = ?");
        $updatePassword->bind_param('si', $newPassword, $userId);
        $updatePassword->execute();

        // Delete the token
        $deleteToken = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $deleteToken->bind_param('s', $token);
        $deleteToken->execute();

        // Set success flag
        $success = true;
    } else {
        echo "Invalid reset link.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            text-align: left;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .modal-content button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-content button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <form method="POST" action="">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="account_type" value="<?php echo htmlspecialchars($accountType); ?>">
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>
            <br>
            <label for="repeat_password">Repeat Password:</label>
            <input type="password" id="repeat_password" name="repeat_password" required>
            <br>
            <button type="submit">Reset Password</button>
        </form>
    </div>

    <?php if ($success): ?>
    <div class="modal" id="successModal">
        <div class="modal-content">
            <p>Password successfully reset!</p>
            <button onclick="redirectToLogin()">OK</button>
        </div>
    </div>
    <script>
        document.getElementById('successModal').style.display = 'flex';
        function redirectToLogin() {
            window.location.href = "<?php echo $redirectUrl; ?>";
        }
    </script>
    <?php endif; ?>
</body>
</html>