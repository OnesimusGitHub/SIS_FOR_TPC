<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header('Location: studentLogin.php');
    exit();
}

require 'db_connection.php'; // Ensure this file contains the database connection logic

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: studentLogin.php'); // Redirect to login page
    exit();
}

$studentId = $_SESSION['student_id']; // Use session variable to fetch the correct student data

// Fetch student details from tblshsstudent
$sql = "SELECT shsstud_ID, shstud_firstname, shstud_middlename, shstud_lastname, shstud_dob, shstud_age, shstud_cadd, shstud_contactno, shstud_email, shstud_sex, shstud_pfp 
        FROM tblshsstudent 
        WHERE shsstud_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $studentId); // Bind as a string
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "Error: Student details not found.";
    exit();
}

// Decode the profile picture (assumes it's stored as a blob in the database)
$profilePicture = !empty($student['shstud_pfp']) ? 'data:image/jpeg;base64,' . base64_encode($student['shstud_pfp']) : 'default-profile.jpg';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate new password constraints
    if (strlen($newPassword) < 8 || !preg_match('/\d/', $newPassword)) {
        $error = "New password must be at least 8 characters long and contain at least 1 number.";
        $keepModalOpen = true; // Flag to keep the modal open
    } else {
        // Validate current password
        $sql = "SELECT shslogin_password FROM tblshslogin WHERE shsstud_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $studentId); // Bind as a string
        $stmt->execute();
        $stmt->bind_result($storedPassword);
        $stmt->fetch();
        $stmt->close();

        if ($currentPassword === $storedPassword) { // Direct comparison for plain text passwords
            if ($newPassword !== $confirmPassword) {
                $error = "New passwords do not match.";
                $keepModalOpen = true; // Flag to keep the modal open
            } else {
                // Update password in the database
                $sql = "UPDATE tblshslogin SET shslogin_password = ? WHERE shsstud_ID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ss', $newPassword, $studentId); // Bind as strings
                if ($stmt->execute()) {
                    // Redirect to avoid form resubmission
                    header('Location: studentPROFILE.php?success=1');
                    exit();
                } else {
                    $error = "Failed to update password.";
                    $keepModalOpen = true; // Flag to keep the modal open
                }
                $stmt->close();
            }
        } else {
            $error = "Current password is incorrect.";
            $keepModalOpen = true; // Flag to keep the modal open
        }
    }
}

// Handle success message after redirect
$success = isset($_GET['success']) && $_GET['success'] == 1 ? "Password updated successfully." : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="studentPROFILE.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .modal-content img {
            width: 50px;
            margin-bottom: 10px;
        }

        .modal-content h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .modal-content p {
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
        }

        .modal-content label {
            display: block;
            text-align: left;
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .modal-content .password-toggle {
            position: relative;
        }

        .modal-content .password-toggle i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }

        .modal-content button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background-color: #0056b3;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }

        .logout-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .logout-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        .logout-modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .logout-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .logout-modal-content .confirm-logout {
            background-color: #007bff;
            color: white;
        }

        .logout-modal-content .confirm-logout:hover {
            background-color: #0056b3;
        }

        .logout-modal-content .cancel-logout {
            background-color: #f2f2f2;
            color: #333;
        }

        .logout-modal-content .cancel-logout:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <!-- Top Header -->
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Student</span>
                <a href="javascript:void(0);" class="logout-button" onclick="showLogoutModal()">Logout</a> <!-- Logout button -->
            </div>
        </div>
        <br>
        <!-- Sub Header -->
        <div class="sub-main">
            <div class="sub-header">
                <div class="menu-icon" id="burger-menu">
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>
                <div class="home-section">
                    <img class="img-home" src="TPC-IMAGES/student.png" alt="Home Icon" class="home-icon">
                    <span class="home-text">PROFILE</span>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="profile-header">
            <img src="<?php echo $profilePicture; ?>" alt="Profile Picture">
            <div>
                <h1><?php echo htmlspecialchars($student['shstud_firstname'] . ' ' . $student['shstud_lastname']); ?></h1>
                <p>LRN: <?php echo htmlspecialchars($student['shsstud_ID']); ?></p> <!-- Changed "ID" to "LRN" -->
            </div>
        </div>
        <div class="section">
            <div>
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" value="<?php echo htmlspecialchars($student['shstud_firstname']); ?>" disabled>
                
                <label for="middle-name">Middle Name</label>
                <input type="text" id="middle-name" value="<?php echo htmlspecialchars($student['shstud_middlename']); ?>" disabled>
                
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" value="<?php echo htmlspecialchars($student['shstud_lastname']); ?>" disabled>
                
                <label for="dob">Date of Birth</label>
                <input type="text" id="dob" value="<?php echo htmlspecialchars($student['shstud_dob']); ?>" disabled>
                
                <label for="age">Age</label>
                <input type="text" id="age" value="<?php echo htmlspecialchars($student['shstud_age']); ?>" disabled>
            </div>
            <div>
                <label for="address">Address</label>
                <input type="text" id="address" value="<?php echo htmlspecialchars($student['shstud_cadd']); ?>" disabled>
                
                <label for="contact">Contact Number</label>
                <input type="text" id="contact" value="<?php echo htmlspecialchars($student['shstud_contactno']); ?>" disabled>
                
                <label for="email">Email</label>
                <input type="text" id="email" value="<?php echo htmlspecialchars($student['shstud_email']); ?>" disabled>
                
                <label for="sex">Sex</label>
                <input type="text" id="sex" value="<?php echo htmlspecialchars($student['shstud_sex']); ?>" disabled>
            </div>
        </div>
        <div class="section">
            <button id="changePasswordBtn" class="change-password-btn">Change Password</button>
        </div>
    </div>

    <!-- Modal for Change Password -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <img src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo">
            <h2>Enter New Password</h2>
            <p>Please enter your new password for <?php echo htmlspecialchars($student['shstud_email']); ?>.</p>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php elseif ($success): ?>
                <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST">
                <label for="current-password">Current Password</label>
                <div class="password-toggle">
                    <input type="password" id="current-password" name="current_password" required>
                    <i class="fa fa-eye" onclick="togglePassword('current-password', this)"></i>
                </div>

                <label for="new-password">New Password</label>
                <div class="password-toggle">
                    <input type="password" id="new-password" name="new_password" required>
                    <i class="fa fa-eye" onclick="togglePassword('new-password', this)"></i>
                </div>

                <label for="confirm-password">Confirm Password</label>
                <div class="password-toggle">
                    <input type="password" id="confirm-password" name="confirm_password" required>
                    <i class="fa fa-eye" onclick="togglePassword('confirm-password', this)"></i>
                </div>

                <button type="submit">Done</button>
            </form>
        </div>
    </div>

    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <h3>Are you sure you want to log out?</h3>
            <form action="" method="GET" style="display: inline;">
                <button type="submit" name="logout" value="true" class="confirm-logout">Yes</button>
            </form>
            <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
        </div>
    </div>

    <!-- Side Menu -->
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="studentHome.php" class="menu-item">Home</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="studentPROFILE.php" class="menu-item">Profile</a></li>
            <li><a href="section_teachers.php" class="menu-item">Schedule</a></li>
            <li><a href="studentGrade.php" class="menu-item">Grades</a></li> <!-- Connected to studentGrade.php -->
            <li class="menu-section-title">ACCOUNTS</li>
            <li><a href="studentPAYMENT.php" class="menu-item">Payment</a></li>
            <li class="menu-section-title">NAVIGATION</li>
            <li><a href="studentMAP.php" class="menu-item">Campus Map</a></li>
        </ul>
    </nav>

    <script>
        const burgerMenu = document.getElementById('burger-menu');
        const sideMenu = document.getElementById('side-menu');
        const closeMenu = document.getElementById('close-menu');

        // Open the menu when clicking the burger icon
        burgerMenu.addEventListener('click', function () {
            sideMenu.classList.add('open');
        });

        // Close the menu when clicking the close button
        closeMenu.addEventListener('click', function () {
            sideMenu.classList.remove('open');
        });

        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const changePasswordModal = document.getElementById('changePasswordModal');
        const closeModal = document.getElementById('closeModal');

        // Open the modal
        changePasswordBtn.addEventListener('click', function () {
            changePasswordModal.style.display = 'block';
        });

        // Close the modal
        closeModal.addEventListener('click', function () {
            changePasswordModal.style.display = 'none';
        });

        // Close the modal when clicking outside of it
        window.addEventListener('click', function (event) {
            if (event.target === changePasswordModal) {
                changePasswordModal.style.display = 'none';
            }
        });

        // Keep the modal open if there is an error
        <?php if (isset($keepModalOpen) && $keepModalOpen): ?>
            document.addEventListener('DOMContentLoaded', function () {
                changePasswordModal.style.display = 'block';
            });
        <?php endif; ?>

        // Toggle password visibility
        function togglePassword(fieldId, icon) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }
    </script>
</body>
</html>