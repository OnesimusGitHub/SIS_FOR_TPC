<?php
session_start();

// Debugging: Check if the session variable is set and valid
if (!isset($_SESSION['registrar_ID']) || empty($_SESSION['registrar_ID'])) {
    header("Location: registrarLogin.php"); // Redirect to registrar login page
    exit();
}

// Debugging: Log the session variable for verification
error_log("Session registrar_ID: " . $_SESSION['registrar_ID']);

require 'db_connection.php'; // Ensure this file contains the database connection logic

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: registrarLogin.php'); // Redirect to login page
    exit();
}

$registrarId = $_SESSION['registrar_ID']; // Use registrar_ID from the session

// Fetch registrar details from tblregistrar table
$sql = "SELECT registrar_ID, registrar_fname, registrar_mname, registrar_lname, registrar_email, registrar_contactno, registrar_dob, registrar_age, registrar_caddress, registrar_paddress, registrar_sex, registrar_exten, registrar_pfp 
        FROM tblregistrar 
        WHERE registrar_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $registrarId); // Bind as an integer
$stmt->execute();
$result = $stmt->get_result();
$registrar = $result->fetch_assoc();

if (!$registrar) {
    // Debugging: Log the issue if registrar details are not found
    error_log("Registrar details not found for registrar_ID: $registrarId");
    echo "Error: Registrar details not found.";
    exit();
}

// Decode the profile picture (assumes it's stored as a blob in the database)
$profilePicture = !empty($registrar['registrar_pfp']) ? 'data:image/jpeg;base64,' . base64_encode($registrar['registrar_pfp']) : 'default-profile.jpg';

// Handle form submission for password change
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
        $sql = "SELECT loginpass FROM login WHERE registrar_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $registrarId); // Bind as an integer
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
                $sql = "UPDATE login SET loginpass = ? WHERE registrar_ID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $newPassword, $registrarId); // Bind as strings
                if ($stmt->execute()) {
                    // Redirect to avoid form resubmission
                    header('Location: registrarPROFILE.php?success=1');
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
    <title>Registrar Profile</title>
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

        /* Side Menu */
.side-menu {
    display: none; /* Initially hidden */
    position: fixed;
    top: 0;
    left: 0;
    width: 270px; /* Width of the side menu */
    height: 100%;
    background-color: #0033cc; /* Blue background */
    color: white;
    padding: 20px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    overflow-y: auto;
}

.side-menu .close-button {
    background: none;
    border: none;
    color: white;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
    display: block;
    margin-bottom: 20px;
}

.side-menu ul {
    list-style: none;
    padding: 0;
}

.side-menu ul li {
    margin: 15px 0;
}

.side-menu ul li.menu-section-title {
    font-size: 14px;
    text-transform: uppercase;
    font-weight: bold;
    margin-top: 20px;
    color: white;
    border-top: 1px solid white;
    padding-top: 10px;
}

.side-menu ul li a {
    text-decoration: none;
    display: block;
    background-color: #cce0ff; /* Light blue for buttons */
    color: #003366; /* Dark blue text */
    padding: 10px 15px;
    border-radius: 5px;
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.side-menu ul li a:hover {
    background-color: #b3ccff; /* Slightly darker on hover */
}

/* Open and Close Animations */
.side-menu.open {
    display: block;
    animation: slideIn 0.3s forwards;
}

@keyframes slideIn {
    from {
        transform: translateX(-300px);
    }
    to {
        transform: translateX(0);
    }
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
                <span class="user-role">Registrar</span>
                <a href="?logout=true" class="logout-button">Logout</a> <!-- Logout button -->
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
                    <img class="img-home" src="TPC-IMAGES/teacher1-removebg-preview.png" alt="Home Icon" class="home-icon">
                    <span class="home-text">PROFILE</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Side Menu -->
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="cashier_dashboard.php" class="menu-item">Dashboard</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="registrarPROFILE.php" class="menu-item">Profile</a></li>
         
            
        </ul>
    </nav>

    <div class="container">
        <div class="profile-header">
            <img src="<?php echo $profilePicture; ?>" alt="Profile Picture">
            <div>
                <h1><?php echo htmlspecialchars($registrar['registrar_fname'] . ' ' . $registrar['registrar_lname']); ?></h1>
                <p>ID: <?php echo htmlspecialchars($registrar['registrar_ID']); ?></p>
            </div>
        </div>
        <div class="section">
            <div>
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" value="<?php echo htmlspecialchars($registrar['registrar_fname']); ?>" disabled>
                
                <label for="middle-name">Middle Name</label>
                <input type="text" id="middle-name" value="<?php echo htmlspecialchars($registrar['registrar_mname'] ?? 'N/A'); ?>" disabled>
                
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" value="<?php echo htmlspecialchars($registrar['registrar_lname']); ?>" disabled>
                
                <label for="extensions">Extensions</label>
                <input type="text" id="extensions" value="<?php echo htmlspecialchars($registrar['registrar_exten'] ?? 'N/A'); ?>" disabled>
                
                <label for="dob">Date of Birth</label>
                <input type="text" id="dob" value="<?php echo htmlspecialchars($registrar['registrar_dob']); ?>" disabled>
                
                <label for="age">Age</label>
                <input type="text" id="age" value="<?php echo htmlspecialchars($registrar['registrar_age'] ?? 'N/A'); ?>" disabled>
            </div>
            <div>
                <label for="current-address">Current Address</label>
                <input type="text" id="current-address" value="<?php echo htmlspecialchars($registrar['registrar_caddress']); ?>" disabled>
                
                <label for="permanent-address">Permanent Address</label>
                <input type="text" id="permanent-address" value="<?php echo htmlspecialchars($registrar['registrar_paddress'] ?? 'N/A'); ?>" disabled>
                
                <label for="contact-number">Contact Number</label>
                <input type="text" id="contact-number" value="<?php echo htmlspecialchars($registrar['registrar_contactno']); ?>" disabled>
                
                <label for="email">Email</label>
                <input type="text" id="email" value="<?php echo htmlspecialchars($registrar['registrar_email']); ?>" disabled>
                
                <label for="sex">Sex</label>
                <input type="text" id="sex" value="<?php echo htmlspecialchars($registrar['registrar_sex'] ?? 'N/A'); ?>" disabled>
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
            <p>Please enter your new password for your account.</p>
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

    <script>
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
    </script>
</body>
</html>