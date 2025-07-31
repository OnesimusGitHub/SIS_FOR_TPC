<?php
session_start();

// Redirect to login page if the teacher is not logged in
if (!isset($_SESSION["teacherid"]) || empty($_SESSION["teacherid"])) {
    header("Location: teacherLogin.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: teacherLogin.php'); // Redirect to login page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Campus </title>
    <link rel="stylesheet" href="teacherMAP.css">
    <style>
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
                <span class="user-role">Teacher</span>
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
                    <img class="img-home" src="TPC-IMAGES/navigation.png" alt="Home Icon" class="home-icon">
                    <span class="home-text">CAMPUS</span>
                </div>
            </div>  
         </div>
     
         </header>

 
    <!-- Side Menu -->
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="teacher_dashboard.php" class="menu-item">Dashboard</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="teacherPROFILE.php" class="menu-item">Profile</a></li>
            <li><a href="teacherSCHEDULE.php" class="menu-item">Schedule</a></li>
            <li><a href="teacherCLASSLIST.php" class="menu-item">Classlist</a></li>
            <li><a href="teacherHome.php" class="menu-item">Announcements</a></li> <!-- Added link to teacherHome.php -->
            <li class="menu-section-title">NAVIGATION</li>
            <li><a href="teacherMAP.php" class="menu-item">Campus Map</a></li>
        </ul>
    </nav>


    <!-- Content Section -->
    <div class="content-section">
        <div class="left-section">
            <button class="arrow-button" onclick="showPreviousImage()">&lt;</button>
        </div>
        <div class="image-container">
            <img id="displayed-image" src="TPC-IMAGES/school.png" alt="Campus Image">
        </div>
        <div class="right-section">
            <button class="arrow-button" onclick="showNextImage()">&gt;</button>
        </div>
        <div class="button-container">
            <button class="nav-button" onclick="changeImage('school')">School</button>
            <button class="nav-button" onclick="changeImage('room')">Room</button>
            <button class="nav-button" onclick="changeImage('library')">Library</button>
            <button class="nav-button" onclick="changeImage('gym')">Gym</button>
            <button class="nav-button" onclick="changeImage('chemistryLab')">Chemistry Lab</button>
            <button class="nav-button" onclick="changeImage('computerLab')">Computer Lab</button>
            <button class="nav-button" onclick="changeImage('kitchen')">Kitchen</button>
            <button class="nav-button" onclick="changeImage('facultyRoom')">Faculty Room</button>
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

    <script src="teacherMAP.js"></script>
    <script>
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }
    </script>
</body>
</html>
