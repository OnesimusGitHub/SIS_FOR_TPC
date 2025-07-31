<?php
// Start the session at the very top of the file
session_start();

// Ensure the session is active and the CSRF token is set
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(); // Start the session if not already started
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
    // Validate the CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<div id='error-modal' class='modal'>
                <div class='modal-content'>
                    <span class='modal-text'><strong>Error:</strong> Invalid CSRF token. Please Go back to the page and try again.</span>
                </div>
              </div>
              <script>
                  const modal = document.getElementById('error-modal');
                  setTimeout(() => {
                      modal.style.display = 'none';
                      window.location.reload(); // Refresh the page after the modal disappears
                  }, 3000);
              </script>
              <style>
                  .modal {
                      display: block;
                      position: fixed;
                      z-index: 1000;
                      left: 0;
                      top: 0;
                      width: 100%;
                      height: 100%;
                      background-color: rgba(0, 0, 0, 0.5);
                      display: flex;
                      justify-content: center;
                      align-items: center;
                  }
                  .modal-content {
                      background-color: white;
                      padding: 20px;
                      border-radius: 5px;
                      text-align: center;
                      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                  }
                  .modal-text {
                      color: red;
                      font-weight: bold;
                  }
              </style>";
        return; // Stop further execution
    }

    // Invalidate the token after use and regenerate a new one
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $password = $_POST['password']; // Use plain text password
    $email = $_POST['email'];
    $contactNumber = $_POST['contact-number'];
    $firstName = $_POST['first-name'];
    $middleName = $_POST['middle-name'];
    $lastName = $_POST['last-name'];
    $extensions = $_POST['extensions'];
    $sex = $_POST['sex'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $currentAddress = $_POST['current-address'];
    $permanentAddress = $_POST['permanent-address'];

    // Handle file upload
    $registrar_pfp = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];

        // Read the file content and encode it as base64
        $registrar_pfp = file_get_contents($fileTmpPath);
    } else {
        die('Error: No file uploaded or upload error.');
    }

    // Check if the email already exists
    $checkDuplicateSql = "SELECT registrar_email FROM tblregistrar WHERE registrar_email = ?";
    $checkDuplicateStmt = $conn->prepare($checkDuplicateSql);
    $checkDuplicateStmt->bind_param('s', $email);
    $checkDuplicateStmt->execute();
    $checkDuplicateStmt->store_result();

    if ($checkDuplicateStmt->num_rows > 0) {
        echo "<div id='error-modal' class='modal'>
                <div class='modal-content'>
                    <span class='modal-text'><strong>Error:</strong> The Email you entered already exists in our records. Please use a different Email.</span>
                </div>
              </div>
              <script>
                  const modal = document.getElementById('error-modal');
                  setTimeout(() => {
                      modal.style.display = 'none';
                      window.location.reload(); // Refresh the page after the modal disappears
                  }, 3000);
              </script>
              <style>
                  .modal {
                      display: block;
                      position: fixed;
                      z-index: 1000;
                      left: 0;
                      top: 0;
                      width: 100%;
                      height: 100%;
                      background-color: rgba(0, 0, 0, 0.5);
                      display: flex;
                      justify-content: center;
                      align-items: center;
                  }
                  .modal-content {
                      background-color: white;
                      padding: 20px;
                      border-radius: 5px;
                      text-align: center;
                      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                  }
                  .modal-text {
                      color: red;
                      font-weight: bold;
                  }
              </style>";
        $checkDuplicateStmt->close();
        return; // Stop further execution without closing the connection
    }

    // Insert data into the tblregistrar table
    $sql = "INSERT INTO tblregistrar (registrar_email, registrar_contactno, registrar_fname, registrar_mname, registrar_lname, registrar_exten, registrar_sex, registrar_dob, registrar_age, registrar_caddress, registrar_paddress, registrar_pfp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssssssssssss',
        $email,
        $contactNumber,
        $firstName,
        $middleName,
        $lastName,
        $extensions,
        $sex,
        $dob,
        $age,
        $currentAddress,
        $permanentAddress,
        $registrar_pfp // Store the binary content of the image
    );

    if ($stmt->execute()) {
        // Get the last inserted registrar_ID
        $registrarId = $conn->insert_id;

        // Insert data into the tblregistrarlogin table
        $loginSql = "INSERT INTO tblregistrarlogin (registrarlogin_email, registrarlogin_password, registrar_ID) VALUES (?, ?, ?)";
        $loginStmt = $conn->prepare($loginSql);
        $loginStmt->bind_param('ssi', $email, $password, $registrarId);

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
                               <strong>Email:</strong> $email<br>
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
        echo "<div style='color: red; font-weight: bold; text-align: center; margin-top: 20px;'>
                Error: Unable to add registrar to the database.
              </div>";
    }

    $stmt->close();

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
    <link href="admin_student_addSHS.css" rel="stylesheet">
    <title>Cashier Information Form</title>
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
    <br><br><br><br><br><br>
    <div class="main-image">
        <div class="main-registrar">
            <h2>Cashier Information</h2>
            <form method="POST" enctype="multipart/form-data"> <!-- Add enctype for file upload -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name">
                    </div>
                    <div class="form-group half-width">
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="middle-name">
                    </div>
                    <div class="form-group half-width">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name">
                    </div>
                    <div class="form-group half-width">
                        <label for="extensions">Extensions</label>
                        <input type="text" id="extensions" name="extensions">
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
                        <label for="password">Password</label>
                        <input type="text" id="password" name="password" value="<?php echo generateRandomPassword(); ?>" readonly>
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
                        <label for="current-address">Current Address</label>
                        <input type="text" id="current-address" name="current-address">
                    </div>
                    <div class="form-group half-width">
                        <label for="permanent-address">Permanent Address</label>
                        <input type="text" id="permanent-address" name="permanent-address">
                    </div>
                </div>
                <div class="form-row add-image">
                    <div class="form-group half-width">
                        <label for="image">Profile Picture</label>
                        <input type="file" id="image" name="image" accept="image/*">
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
            <h3>Cashier added successfully!</h3>
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
