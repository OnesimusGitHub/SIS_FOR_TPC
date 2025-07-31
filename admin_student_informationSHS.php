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

// Get the student ID from the query string
$studentId = $_GET['studentid'] ?? null;

if (!$studentId) {
    die('Error: No student ID provided.');
}

// Fetch the student data from the database
$sql = "SELECT shsstud_ID, shstud_email, shstud_contactno, shstud_firstname, shstud_middlename, shstud_lastname, shstud_extensions, shstud_sex, shstud_dob, shstud_age, shstud_cadd, shstud_padd, shstud_pfp 
        FROM tblshsstudent 
        WHERE shsstud_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Error: Student not found.');
}

$student = $result->fetch_assoc();
$stmt->close();

// Handle form submission for updating student information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
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

    // Handle profile picture upload
    $profilePicture = $student['shstud_pfp']; // Default to existing picture
    if (isset($_FILES['profile-picture']) && $_FILES['profile-picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile-picture']['tmp_name'];
        $profilePicture = file_get_contents($fileTmpPath);
    }   

    // Update the student data in the database
    $updateSql = "UPDATE tblshsstudent 
                  SET shstud_email = ?, shstud_contactno = ?, shstud_firstname = ?, shstud_middlename = ?, shstud_lastname = ?, shstud_extensions = ?, shstud_sex = ?, shstud_dob = ?, shstud_age = ?, shstud_cadd = ?, shstud_padd = ?, shstud_pfp = ? 
                  WHERE shsstud_ID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param(
        'sssssssssssss',
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
        $profilePicture,
        $studentId
    );

    if ($updateStmt->execute()) {
        // Redirect with a success flag to show the modal
        header("Location: " . $_SERVER['PHP_SELF'] . "?studentid=" . $studentId . "&update_success=true");
        exit();
    } else {
        echo '<script>alert("Error updating student information.");</script>';
    }

    $updateStmt->close();
}

$conn->close();
?>

<?php if (isset($_GET['update_success']) && $_GET['update_success'] === 'true'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('successModal');
            modal.style.display = 'block';

            // Close the modal after 3 seconds
            setTimeout(() => {
                modal.style.display = 'none';
            }, 3000);
        });
    </script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Information</title>
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
        .main-image {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-top: 50px;
        }
        .main-student {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 800px;
        }
        .main-student h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .student-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .student-info label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .student-info input, .student-info select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .student-info img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
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
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .modal-content h3 {
            margin: 0;
            color: #28a745;
        }
        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-picture-container input[type="file"] {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo">
        </div>
        <nav>
            <a href="manageStudent.php">Back</a>
        </nav>
    </header>
    <div class="main-image">
        <div class="main-student">
            <h2>Student Information</h2>
            <form method="POST" enctype="multipart/form-data"> <!-- Add enctype for file upload -->
                <div class="student-info">
                    <div class="profile-picture-container">
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($student['shstud_pfp']); ?>" alt="Student Picture" class="profile-picture">
                        <input type="file" name="profile-picture" accept="image/*">
                    </div>
                    <div style="flex: 1;">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['shstud_email']); ?>" readonly>
                        
                        <label for="contact-number">Contact Number</label>
                        <input type="text" id="contact-number" name="contact-number" value="<?php echo htmlspecialchars($student['shstud_contactno']); ?>">

                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" value="<?php echo htmlspecialchars($student['shstud_firstname']); ?>" required>

                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="middle-name" value="<?php echo htmlspecialchars($student['shstud_middlename']); ?>">

                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" value="<?php echo htmlspecialchars($student['shstud_lastname']); ?>" required>

                        <label for="extensions">Extensions</label>
                        <input type="text" id="extensions" name="extensions" value="<?php echo htmlspecialchars($student['shstud_extensions']); ?>">

                        <label for="sex">Sex</label>
                        <select id="sex" name="sex" required>
                            <option value="Male" <?php echo $student['shstud_sex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $student['shstud_sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>

                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($student['shstud_dob']); ?>" required>

                        <label for="age">Age</label>
                        <input type="text" id="age" name="age" value="<?php echo htmlspecialchars($student['shstud_age']); ?>" readonly>

                        <label for="current-address">Current Address</label>
                        <input type="text" id="current-address" name="current-address" value="<?php echo htmlspecialchars($student['shstud_cadd']); ?>">

                        <label for="permanent-address">Permanent Address</label>
                        <input type="text" id="permanent-address" name="permanent-address" value="<?php echo htmlspecialchars($student['shstud_padd']); ?>">
                    </div>
                </div>
                <div class="submit-btn">
                    <button type="submit" name="update">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h3>Student information updated successfully!</h3>
        </div>
    </div>
</body>
</html>