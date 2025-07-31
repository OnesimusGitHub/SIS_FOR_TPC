<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sis";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the email exists in the login table
        $stmt = $pdo->prepare("SELECT loginpass FROM login WHERE loginuser = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $password = $user["loginpass"];

            // Send email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Use your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'delacruzonesimuspalles@gmail.com'; // Your email
                $mail->Password = 'iliaaewJefwzlwai'; // Your email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('delacruzonesimuspalles@gmail.com', 'Admin');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Recovery';
                $mail->Body = "Hello,<br><br>Your password is: <strong>$password</strong><br><br>Please keep it secure.";

                $mail->send();
                echo json_encode(["success" => "Password has been sent to your email."]);
            } catch (Exception $e) {
                echo json_encode(["error" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
            }
        } else {
            echo json_encode(["error" => "Email not found in our records."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="studentForgetpass.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Forget Password</title>
</head>
<body>
    <div class="headers">
        <div class="header1">
            <div class="headerIMG">
                <img class="img1" src="TPC-IMAGES/logos-removebg-preview.png">
            </div>
            <div class="text-headers">TPC</div>
        </div>
        <div class="header2">
            <a href="teacherLogin.html" class="back-link">
                <i class="fa-solid fa-hand-point-left fa-2xl"></i>
            </a>
        </div>
    </div>
    <div class="main">
        <div class="container">
            <h1>Forget Password</h1>
            <p>Enter your registered email address to recover your password:</p>
            <form id="forgetPasswordForm" method="POST">
                <label for="email">Enter Email:</label>
                <input type="email" id="email" name="email" required>
                <div class="error-message" id="errorMessage">Please enter correct E-mail.</div>
                <div class="buttons">
                    <button type="submit" class="submit-button">Submit</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>