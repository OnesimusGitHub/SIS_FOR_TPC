<?php
require 'db_connection.php';
require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$messageSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $accountType = trim($_POST['account_type']); // Get the account type

    // Determine the table, email column, and ID column based on the account type
    $table = '';
    $emailColumn = '';
    $idColumn = '';
    $redirectUrl = '';
    switch ($accountType) {
        case 'student':
            $table = 'tblshslogin';
            $emailColumn = 'shslogin_email';
            $idColumn = 'shslogin_ID';
            $redirectUrl = 'studentLogin.php';
            break;
        case 'teacher':
            $table = 'login';
            $emailColumn = 'loginuser';
            $idColumn = 'loginid';
            $redirectUrl = 'teacherLogin.php';
            break;
        case 'registrar':
            $table = 'tblregistrarlogin';
            $emailColumn = 'registrarlogin_email';
            $idColumn = 'registrarlogin_ID';
            $redirectUrl = 'registrarLogin.php';
            break;
        case 'admin':
            $table = 'tbladminlogin';
            $emailColumn = 'adminlogin_email';
            $idColumn = 'adminlogin_ID';
            $redirectUrl = 'adminLogin.php';
            break;
        default:
            die('Invalid account type.');
    }

    // Check if the email exists in the database
    $sql = "SELECT $idColumn FROM $table WHERE $emailColumn = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId);
        $stmt->fetch();

        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 3600; // Token valid for 1 hour
        $expiryDate = date('Y-m-d H:i:s', $expiry);

        // Save the token and expiry in the database
        $insertToken = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry, account_type) VALUES (?, ?, ?, ?)");
        $insertToken->bind_param('isss', $userId, $token, $expiryDate, $accountType);
        $insertToken->execute();

        // Send the reset email using PHPMailer
        $resetLink = "http://localhost/Php/resetPassword.php?token=$token&account_type=$accountType";
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'delacruzonesimuspalles@gmail.com';
                $mail->Password = 'iliaaewjewfzlwai'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('your_email@gmail.com', 'Admin');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a>";

            $mail->send();
            $messageSent = true;
        } catch (Exception $e) {
            echo "Failed to send the email. Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
            font-weight: bold;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            text-align: left;
            font-size: 14px;
            color: #555;
        }
        input, select, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        input:focus, select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-button {
            background-color: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #5a6268;
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
            z-index: 1000;
        }
        .modal-content {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 400px;
        }
        .modal-content p {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
        }
        .modal-content button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .modal-content button:hover {
            background-color: #0056b3;
        }
        @media (max-width: 500px) {
            .container {
                padding: 20px;
            }
            h1 {
                font-size: 20px;
            }
            input, select, button {
                font-size: 12px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <form method="POST" action="">
            <label for="email">Enter your email:</label>
            <input type="email" id="email" name="email" placeholder="example@domain.com" required>
            <label for="account_type">Account Type:</label>
            <select id="account_type" name="account_type" required>
                <option value="" disabled selected>Select your account type</option>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="registrar">Registrar</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Send Reset Link</button>
        </form>
        <button class="back-button" onclick="window.location.href='Allchoose.php';">Back to Login</button>
    </div>

    <?php if ($messageSent): ?>
    <div class="modal" id="successModal">
        <div class="modal-content">
            <p>Message sent successfully! Please check your inbox for the reset link.</p>
            <button onclick="closeModal()">Close</button>
        </div>
    </div>
    <script>
        document.getElementById('successModal').style.display = 'flex';
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }
    </script>
    <?php endif; ?>
</body>
</html>