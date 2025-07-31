<?php
// Start the session at the very top of the file
session_start();

// Ensure the session is active and the CSRF token is set
if (session_status() !== PHP_SESSION_ACTIVE) {
    die('Session not started properly.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a CSRF token
}

// Prevent browser caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Content-Type: text/html; charset=UTF-8");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the registrar ID from the query string
$registrarId = $_GET['registrarid'] ?? null;

if (!$registrarId) {
    die('Error: No registrar ID provided.');
}

// Fetch the registrar data from the database
$sql = "SELECT * FROM tblregistrar WHERE registrar_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $registrarId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Error: Registrar not found.');
}

$registrar = $result->fetch_assoc();
$stmt->close();

// Handle form submission for updating registrar information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $firstName = $_POST['first-name'];
    $middleName = $_POST['middle-name'];
    $lastName = $_POST['last-name'];
    $email = $_POST['email'];
    $contactNumber = $_POST['contact-number'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $currentAddress = $_POST['current-address'];
    $permanentAddress = $_POST['permanent-address'];
    $extensions = $_POST['extensions'];
    $sex = $_POST['sex'];

    // Handle picture upload
    $imageData = null;
    if (isset($_FILES['registrar-picture']) && $_FILES['registrar-picture']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['registrar-picture']['tmp_name']);
    }

    if ($imageData !== null) {
        $updateSql = "UPDATE tblregistrar 
                      SET registrar_fname = ?, registrar_mname = ?, registrar_lname = ?, registrar_email = ?, registrar_contactno = ?, 
                          registrar_dob = ?, registrar_age = ?, registrar_caddress = ?, registrar_paddress = ?, registrar_exten = ?, 
                          registrar_sex = ?, registrar_pfp = ?
                      WHERE registrar_ID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param(
            'sssssssssssss',
            $firstName,
            $middleName,
            $lastName,
            $email,
            $contactNumber,
            $dob,
            $age,
            $currentAddress,
            $permanentAddress,
            $extensions,
            $sex,
            $imageData,
            $registrarId
        );
    } else {
        $updateSql = "UPDATE tblregistrar 
                      SET registrar_fname = ?, registrar_mname = ?, registrar_lname = ?, registrar_email = ?, registrar_contactno = ?, 
                          registrar_dob = ?, registrar_age = ?, registrar_caddress = ?, registrar_paddress = ?, registrar_exten = ?, 
                          registrar_sex = ?
                      WHERE registrar_ID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param(
            'ssssssssssss',
            $firstName,
            $middleName,
            $lastName,
            $email,
            $contactNumber,
            $dob,
            $age,
            $currentAddress,
            $permanentAddress,
            $extensions,
            $sex,
            $registrarId
        );
    }

    if ($updateStmt->execute()) {
        echo '<script>alert("Registrar information updated successfully!");</script>';
        // Refresh the page to reflect updated data
        header("Location: " . $_SERVER['PHP_SELF'] . "?registrarid=" . $registrarId);
        exit();
    } else {
        echo '<script>alert("Error updating registrar information.");</script>';
    }

    $updateStmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Information</title>
    <style>
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #003366;
            padding: 10px 20px;
        }
        header .logo img {
            height: 50px;
        }
        header nav a {
            text-decoration: none;
            color: white;
            font-size: 16px;
        }
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-top: 50px;
        }
        .main-instructor {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 800px;
        }
        .main-instructor h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .form-group ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .form-group ul li {
            margin: 5px 0;
        }
        .submit-btn {
            text-align: center;
            margin-top: 20px;
        }
        .submit-btn button {
            background-color: #003366;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn button:hover {
            background-color: #00509e;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo">
        </div>
        <nav>
            <a href="registrarAccounts.php">Back</a>
        </nav>
    </header>
    <div class="main-container">
        <div class="main-instructor">
            <h2>Registrar Information</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="registrar-id">Registrar ID</label>
                        <input type="text" id="registrar-id" value="<?php echo htmlspecialchars($registrar['registrar_ID']); ?>" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" value="<?php echo htmlspecialchars($registrar['registrar_fname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="middle-name" value="<?php echo htmlspecialchars($registrar['registrar_mname']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" value="<?php echo htmlspecialchars($registrar['registrar_lname']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($registrar['registrar_email']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact-number">Contact Number</label>
                        <input type="text" id="contact-number" name="contact-number" value="<?php echo htmlspecialchars($registrar['registrar_contactno']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($registrar['registrar_dob']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="text" id="age" name="age" value="<?php echo htmlspecialchars($registrar['registrar_age']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="current-address">Current Address</label>
                        <input type="text" id="current-address" name="current-address" value="<?php echo htmlspecialchars($registrar['registrar_caddress']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="permanent-address">Permanent Address</label>
                        <input type="text" id="permanent-address" name="permanent-address" value="<?php echo htmlspecialchars($registrar['registrar_paddress']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="extensions">Extensions</label>
                        <input type="text" id="extensions" name="extensions" value="<?php echo htmlspecialchars($registrar['registrar_exten']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="sex">Sex</label>
                        <input type="text" id="sex" name="sex" value="<?php echo htmlspecialchars($registrar['registrar_sex']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="registrar-picture">Registrar Picture</label>
                        <?php if (!empty($registrar['registrar_pfp'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($registrar['registrar_pfp']); ?>" alt="Registrar Picture" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            <p>No picture available</p>
                        <?php endif; ?>
                        <input type="file" id="registrar-picture" name="registrar-picture" accept="image/*">
                    </div>
                </div>
                <div class="submit-btn">
                    <button type="submit" name="update">Update</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
