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

// Include PHPMailer manually
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "sis"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate a random 8-character password
function generateRandomPassword() {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < 8; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Debugging: Check if the CSRF token is sent in the form
    if (!isset($_POST['csrf_token'])) {
        die('CSRF token not sent in the form.');
    }

    // Uncomment the following line for debugging purposes
    // echo "Received CSRF Token: " . $_POST['csrf_token'] . "<br>";

    // Validate the CSRF token
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    // Invalidate the token after use and regenerate a new one
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $email = $_POST['email'];
    $password = $_POST['password']; // Store the plain password
    $firstName = $_POST['first-name'];
    $middleName = $_POST['middle-name'];
    $lastName = $_POST['last-name'];
    $extensions = $_POST['extensions'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $currentAddress = $_POST['current-address'];
    $permanentAddress = $_POST['permanent-address'];
    $contactNumber = $_POST['contact-number'];
    $sex = $_POST['sex'];

    // Handle file upload
    if (isset($_FILES['teacher-pic']) && $_FILES['teacher-pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['teacher-pic']['tmp_name'];
        $fileContent = file_get_contents($fileTmpPath);
    } else {
        die('Error uploading file');
    }

    // Check if the loginuser already exists
    $checkSql = "SELECT COUNT(*) FROM login WHERE loginuser = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $checkStmt->bind_result($exists);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($exists) {
        echo '<script>alert("The email already exists. Please choose a different email.");</script>';
    } else {
        // Insert data into the teachrinf table
        $sql = "INSERT INTO teachrinf (teacher_email, teacher_pass, teachername, teachermidd, teacherlastname, teacher_extensions, teacher_dob, teacher_age, teacher_ca, teacher_padd, teacher_contactno, teacher_sex, teacher_pfp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssssssssssss',
            $email,
            $password, // Store the plain password
            $firstName,
            $middleName,
            $lastName,
            $extensions,
            $dob,
            $age,
            $currentAddress,
            $permanentAddress,
            $contactNumber,
            $sex,
            $fileContent
        );

        if ($stmt->execute()) {
            // Insert data into the login table
            $loginSql = "INSERT INTO login (loginuser, loginpass, teacherid) VALUES (?, ?, ?)";
            $loginStmt = $conn->prepare($loginSql);
            $teacherId = $conn->insert_id; // Get the last inserted ID from the teachrinf table
            $loginStmt->bind_param('ssi', $email, $password, $teacherId); // Store the plain password

            if ($loginStmt->execute()) {
                // Send email with PHPMailer
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'delacruzonesimuspalles@gmail.com';
                    $mail->Password = 'iliaaewjewfzlwai';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('delacruzonesimuspalles@gmail.com', 'Admin');
                    $mail->addAddress($email, "$firstName $lastName");

                    $mail->isHTML(true);
                    $mail->Subject = 'Your Account Details';
                    $mail->Body = "Dear $firstName $lastName $extensions,<br><br>Your account has been created. Here are your login details:<br><br>
                                   <strong>Password:</strong> $password<br>
                                   <strong>Date of Birth:</strong> $dob<br>
                                   <strong>Age:</strong> $age<br>
                                   <strong>Current Address:</strong> $currentAddress<br>
                                   <strong>Permanent Address:</strong> $permanentAddress<br>
                                   <strong>Sex:</strong> $sex<br><br>
                                   Please keep this information secure.<br><br>Regards,<br>Admin";

                    $mail->send();
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            document.getElementById("loadingModal").style.display = "none";
                            document.getElementById("successModal").style.display = "flex";
                        });
                    </script>';
                } catch (Exception $e) {
                    echo '<script>alert("Error: ' . $mail->ErrorInfo . '");</script>';
                }
            } else {
                echo '<script>alert("Error: Unable to add login details to the database.");</script>';
            }

            $loginStmt->close();
        } else {
            echo '<script>alert("Error: Unable to add instructor to the database.");</script>';
        }

        $stmt->close();
    }

    // Redirect to the same page to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="admin_instructor_add.css" rel="stylesheet">
    <title>Instructor Information Form</title>
    <script>
        // Function to calculate age based on Date of Birth
        function calculateAge() {
            const dobField = document.getElementById('dob');
            const ageField = document.getElementById('age');
            const dob = new Date(dobField.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            ageField.value = age > 0 ? age : ''; // Set age or clear if invalid
        }
    </script>
</head>
<body>
    <header>
        <div class="logo">
            <img class="img1" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo">
        </div>
        <nav>
            <a href="admin_dashboard.php" class="cta">
                <img class="img2" src="TPC-IMAGES/back.png" alt="Back">
            </a>
        </nav>
    </header>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <div class="main-image">
        <div class="main-student">
            <h2>Instructor Information</h2>
            <form method="POST" enctype="multipart/form-data"> <!-- Add enctype for file upload -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="middle-name">
                    </div>
                    <div class="form-group half-width">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="extensions">Name Extensions</label>
                        <input type="text" id="extensions" name="extensions" placeholder="e.g., Jr., Sr., III">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="contact-number">Contact Number</label>
                        <input type="text" id="contact-number" name="contact-number">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" onchange="calculateAge()" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="age">Age</label>
                        <input type="text" id="age" name="age" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="password">Password</label>
                        <input type="text" id="password" name="password" value="<?php echo generateRandomPassword(); ?>" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="current-address">Current Address</label>
                        <input type="text" id="current-address" name="current-address" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="permanent-address">Permanent Address</label>
                        <input type="text" id="permanent-address" name="permanent-address" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="sex">Sex</label>
                        <select id="sex" name="sex" required>
                            <option value="" disabled selected>Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="teacher-pic">Teacher Picture</label>
                        <input type="file" id="teacher-pic" name="teacher-pic" accept="image/*" required>
                    </div>
                </div>
                <div class="submit-btn">
                    <button type="submit" name="submit">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="loading-modal">
        <div class="spinner"></div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="success-modal">
        <div class="success-modal-content">
            <h3>Instructor added successfully!</h3>
            <button onclick="closeSuccessModal()">OK</button>
        </div>
    </div>

    <script>
        const form = document.querySelector('form'); // Replace with the actual form selector
        form.addEventListener('submit', function () {
            document.getElementById('loadingModal').style.display = 'flex';
        });

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }
    </script>
</body>
</html>