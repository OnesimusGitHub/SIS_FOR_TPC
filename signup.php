<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $teachername = trim($_POST["teachername"]);
    $teachermidd = trim($_POST["teachermidd"]);
    $teacherlastname = trim($_POST["teacherlastname"]);
    $teacherfield = trim($_POST["teacherfield"]);

    $db_username = "root"; 
    $db_password = "";
    $database = "sis";

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$database", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->beginTransaction();

        // Insert into teachrinf table
        $sql = "INSERT INTO teachrinf (teachername, teachermidd, teacherlastname, teacherfield) VALUES (:teachername, :teachermidd, :teacherlastname, :teacherfield)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':teachername', $teachername);
        $stmt->bindParam(':teachermidd', $teachermidd);
        $stmt->bindParam(':teacherlastname', $teacherlastname);
        $stmt->bindParam(':teacherfield', $teacherfield);
        $stmt->execute();
        $teacherid = $pdo->lastInsertId();

        // Insert into login table
        $sql = "INSERT INTO login (loginuser, loginpass, teacherid) VALUES (:username, :password, :teacherid)";
        $stmt = $pdo->prepare($sql);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':teacherid', $teacherid);
        $stmt->execute();

        $pdo->commit();
        $success = "Account created successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Database error: " . $e->getMessage();
    }

    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
</head>
<body>
    <h2>Sign Up</h2>
    <?php
    if (isset($success)) {
        echo "<p style='color: green;'>$success</p>";
    } elseif (isset($error)) {
        echo "<p style='color: red;'>$error</p>";
    }
    ?>
    <form action="" method="POST">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="teachername">First Name:</label><br>
        <input type="text" id="teachername" name="teachername" required><br><br>

        <label for="teachermidd">Middle Name:</label><br>
        <input type="text" id="teachermidd" name="teachermidd" required><br><br>

        <label for="teacherlastname">Last Name:</label><br>
        <input type="text" id="teacherlastname" name="teacherlastname" required><br><br>

        <label for="teacherfield">Field:</label><br>
        <select id="teacherfield" name="teacherfield" required>
            <option value="MATHEMATICS">MATHEMATICS</option>
            <option value="SCIENCE">SCIENCE</option>
            <option value="CHS">CHS</option>
            <option value="ENGLISH">ENGLISH</option>
            <option value="FILIPINO">FILIPINO</option>
        </select><br><br>

        <input type="submit" value="Sign Up">
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>
