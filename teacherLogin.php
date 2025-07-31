<?php
session_start();

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the user is locked out
    if ($_SESSION['login_attempts'] >= 3 && isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $remainingTime = $_SESSION['lockout_time'] - time();
        $error = "Too many failed login attempts. Please try again in $remainingTime seconds.";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        $db_username = "root"; 
        $db_password = "";
        $database = "sis";

        try {
            $pdo = new PDO("mysql:host=localhost;dbname=$database", $db_username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT l.loginid, l.loginpass, l.teacherid, t.teachstat 
                    FROM login l 
                    INNER JOIN teachrinf t ON l.teacherid = t.teacherid 
                    WHERE l.loginuser = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if the teacher is archived
                if ($row["teachstat"] === "ARCHIVED") {
                    $error = "Your account has been archived. Please contact the administrator.";
                } 
                // Check if the password matches (hashed or plain text)
                elseif (password_verify($password, $row["loginpass"]) || $password === $row["loginpass"]) {
                    $_SESSION["loginid"] = $row["loginid"]; // Store login ID in the session
                    $_SESSION["teacherid"] = $row["teacherid"]; // Set teacher ID in session
                    $_SESSION['login_attempts'] = 0; // Reset login attempts on successful login
                    header("Location: teacher_dashboard.php");
                    exit;
                } else {
                    $error = "Invalid password.";
                    $_SESSION['login_attempts']++;
                }
            } else {
                $error = "No account found with that username.";
                $_SESSION['login_attempts']++;
            }

            // Lock the user out if they fail 3 times
            if ($_SESSION['login_attempts'] >= 3) {
                $_SESSION['lockout_time'] = time() + 60; // Lockout for 60 seconds
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }

        unset($pdo);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="teacherLogin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Teacher Login</title>
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
            <img class="img1" src="TPC-IMAGES/logos-removebg-preview.png">
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
                        <h1>Teacher Login</h1>
                        <h6>Welcome back. Enter your credentials to access your account</h6>
                        <?php if (isset($error)): ?>
                            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                        <?php endif; ?>
                        <form action="" method="POST">
                            <label for="username">Email:</label>
                            <input type="text" id="username" name="username" required>
                            
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>

                            <a href="forgotPassword.php" class="forgot-password-link">Forgot Password?</a>
                            
                            <button type="submit" <?php if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) echo 'disabled'; ?>>Login</button>
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
