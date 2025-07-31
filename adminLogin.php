<?php
session_start();
require 'db_connection.php'; // Ensure this file contains the database connection logic

// If the admin is already logged in, redirect to the admin dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

// Initialize login attempt tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the user is locked out
    if ($_SESSION['login_attempts'] >= 3 && isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $remainingTime = $_SESSION['lockout_time'] - time();
        $error = "Too many failed login attempts. Please try again in $remainingTime seconds.";
    } else {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Query to fetch the admin account with matching email
        $sql = "SELECT admin_ID, adminlogin_password FROM tbladminlogin WHERE adminlogin_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($adminId, $storedPassword);
            $stmt->fetch();

            // Verify the password (plain text comparison)
            if ($password === $storedPassword) { // Compare plain password
                $_SESSION['admin_id'] = $adminId; // Set the session variable for the logged-in admin
                $_SESSION['login_attempts'] = 0; // Reset login attempts on successful login
                header('Location: admin_dashboard.php'); // Redirect to the admin dashboard
                exit();
            } else {
                $error = "Invalid email or password.";
                $_SESSION['login_attempts']++;
            }
        } else {
            $error = "Invalid email or password.";
            $_SESSION['login_attempts']++;
        }

        // Lock the user out if they fail 3 times
        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['lockout_time'] = time() + 60; // Lockout for 60 seconds
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="studentLogin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Admin Login</title>
    <style>
        .login-container h1 {
            font-size: 32px;
            color: #0033cc;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .login-container h6 {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="headers">
        <div class="header1">
            <div class="headerIMG">
                <img class="img1" src="TPC-IMAGES/logos-removebg-preview.png" alt="TPC Logo">
            </div>
            <div class="text-headers">TPC</div>
        </div>
        <div class="header2">
            <a href="ALLChoose.php" class="back-link">
                <i class="fa-solid fa-hand-point-left fa-2xl"></i>
            </a>
        </div>
    </div>

    <div class="main">
        <div class="submain">
            <div class="mainsub">
                <div class="mainsub1">
                    <div class="login-container">
                        <h1>Admin Login</h1>
                        <h6>Welcome back. Enter your credentials to access your account</h6>
                        <form id="loginForm" method="POST" action="">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                            
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                            
                            <?php if (isset($error)): ?>
                                <div id="error-message" class="error-message">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <a href="forgotPassword.php" class="forgot-password-link">Forgot Password?</a>
                            
                            <button type="submit" id="loginBtn" <?php if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) echo 'disabled'; ?>>Login</button>
                            <br>
                        </form>
                    </div>
                </div>
                <div class="mainsub2">
                    <div class="mainsub2-1">
                        <img class="mainsub2img1" src="TPC-IMAGES/logos-removebg-preview.png">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
