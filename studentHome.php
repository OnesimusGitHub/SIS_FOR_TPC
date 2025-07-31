<?php
session_start();

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: studentLogin.php");
    exit();
}

// Redirect to login page if the student is not logged in
if (!isset($_SESSION["student_id"]) || empty($_SESSION["student_id"])) {
    header("Location: studentLogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
        }
        
        .main-header {
            border-bottom: 1px solid #ccc;
        }
        
        .img-main {
            height: 8vh;
        }
        
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: black;
        }
        
        .user-section {
            display: flex;
            align-items: center;
        }
        
        .user-role {
            font-size: 16px;
            color: #0078D4; /* Blue color for the text */
            margin-right: 15px;
        }
        
        .logout-button {
            background-color: #0033cc; /* Dark blue background */
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .logout-button:hover {
            background-color: #0055ff; /* Lighter blue on hover */
        }
        
        .sub-main {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50px;
            width: 100%;
        }
        
        .sub-header {
            width: 97%;
            display: flex;
            align-items: center;
            background-color: #0033cc; /* Blue background */
            padding: 10px 20px;
            color: white;
            height: 50px;
        }
        
        .menu-icon {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 20px;
            cursor: pointer;
            margin-right: 20px;
        }
        
        .menu-icon .line {
            width: 25px;
            height: 3px;
            background-color: white;
            border-radius: 2px;
        }
        
        .home-section {
            display: flex;
            align-items: center;
        }
        
        .home-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        
        .home-text {
            font-size: 22px;
            font-weight: bold;
            color: white;
        }
        
        .img-home {
            height: 30px;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
          }
          .container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          }
          .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
          }
          .profile-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 20px;
          }
          .profile-header h1 {
            font-size: 24px;
            margin: 0;
          }
          .profile-header p {
            margin: 5px 0 0;
            color: #666;
          }
          .section {
            display: flex;
            justify-content: space-between;
          }
          .section div {
            width: 48%;
          }
          .section label {
            font-size: 14px;
            color: #666;
          }
          .section input {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        .nav-links {
            display: flex;
            align-items: center;
            margin-right: 0;
        }
        .nav-links a {
            text-decoration: none;
            font-size: 16px;
            color: #000;
            padding: 5px 15px;
            border-radius: 5px;
            display: inline-block;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .nav-links a:hover,
        .nav-links .home:hover,
        .nav-links .aboutus:hover,
        .nav-links .loginportal:hover,
        .nav-links .programs:hover {
            background-color: #001f54;
            color: white;
        }
        .announcement-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .announcement-card {
            background-color: #1e3a5f;
            color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            max-width: 100%;
        }
        .announcement-card.general {
            background-color: #1e3a5f;
        }
        .announcement-card.important {
            background-color: #d9534f;
        }
        .announcement-card.incoming-event {
            background-color: #1e3a5f;
        }
        .announcement-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .announcement-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .announcement-info {
            display: flex;
            flex-direction: column;
        }
        .announcement-author {
            font-weight: bold;
            margin: 0;
        }
        .announcement-time {
            font-size: 0.9rem;
            color: #f0f0f0;
            margin: 0;
        }
        .announcement-type {
            font-size: 1rem;
            margin: 10px 0;
        }
        .announcement-content {
            font-size: 1rem;
            line-height: 1.5;
            margin: 0;
        }
        .announcement-content-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }
        .announcement-text {
            flex: 1;
            max-width: 60%;
        }
        .announcement-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            margin-top: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .no-announcements {
            text-align: center;
            font-size: 1.2rem;
            color: #555;
            margin-top: 20px;
        }

        /* Alternative Announcement Styles */
        .announcement {
            margin: 15px auto;
            padding: 20px; /* Reduced padding */
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 10px; /* Slightly smaller border radius */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px; /* Reduced max width */
        }

        .announcement h2 {
            font-size: 22px; /* Reduced font size */
            color: #0d47a1;
            margin-bottom: 15px; /* Reduced margin */
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        #announcement-container {
            display: flex;
            flex-direction: column;
            gap: 15px; /* Reduced gap */
        }

        .announcement-item {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px; /* Slightly smaller border radius */
            padding: 15px; /* Reduced padding */
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .announcement-item:hover {
            transform: translateY(-3px); /* Reduced hover effect */
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
        }

        .announcement-item p {
            margin: 8px 0; /* Reduced margin */
            font-size: 16px; /* Reduced font size */
            color: #424242;
            line-height: 1.4; /* Slightly tighter line height */
        }

        .announcement-item img {
            width: 100%;
            max-width: 300px; /* Reduced max width */
            height: auto;
            border-radius: 8px; /* Slightly smaller border radius */
            margin-top: 10px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }

        .announcement-item small {
            display: block;
            margin-top: 10px; /* Reduced margin */
            font-size: 13px; /* Reduced font size */
            color: #757575;
            text-align: right;
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
                <span class="user-role">Announcement</span>
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
                    <span class="home-text">Announcement</span>
                </div>
            </div>
        </div>
    </header>

    <div class="announcement">
        <h2>Announcement</h2>
        <div id="announcement-container">Loading announcements...</div>
    </div>

    <!-- Side Menu -->
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="studentHome.php" class="menu-item">Home</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="studentPROFILE.php" class="menu-item">Profile</a></li>
            <li><a href="section_teachers.php" class="menu-item">Schedule</a></li>
            <li><a href="studentGrade.php" class="menu-item">Grades</a></li> <!-- Added link to studentGrades.php -->
            <li class="menu-section-title">ACCOUNTS</li>
            <li><a href="studentPAYMENT.php" class="menu-item">Payment</a></li>
            <li class="menu-section-title">NAVIGATION</li>
            <li><a href="studentMAP.php" class="menu-item">Campus Map</a></li>
        </ul>
    </nav>

    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <h3>Are you sure you want to log out?</h3>
            <form action="" method="GET" style="display: inline;">
                <button type="submit" name="logout" value="true" class="confirm-logout">Yes</button>
            </form>
            <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
        </div>
    </div>

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

        fetch('fetchAnnouncements.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('announcement-container');
                container.innerHTML = data.map(announcement => `
                    <div class="announcement-item">
                        <p><strong>Type:</strong> ${announcement.type}</p>
                        <p>${announcement.announce_content}</p>
                        ${announcement.image_path ? `<img src="${announcement.image_path}" alt="Announcement Image" class="announcement-image">` : ''}
                        <small><strong>Posted on:</strong> ${announcement.announcement_ct}</small>
                    </div>
                `).join('');
            })
            .catch(error => {
                document.getElementById('announcement-container').textContent = "No announcements available.";
            });

        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }
    </script>
</body>
</html>