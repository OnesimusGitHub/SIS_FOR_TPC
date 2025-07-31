<?php
session_start();

if (!isset($_SESSION["teacherid"]) || empty($_SESSION["teacherid"])) { // Check if teacherid is set and not empty
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

$teacherID = $_SESSION["teacherid"]; // Get the logged-in teacher's ID

$servername = "localhost";
$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all schedules assigned to the teacher
    $sql = "
        SELECT s.section_Name AS section_name, s.shsgrade AS grade, sch.schedule_time, sch.schedule_date, sch.schedule_room
        FROM tblschedule sch
        INNER JOIN tblshssection s ON sch.section_ID = s.section_ID
        WHERE sch.teacher_ID = :teacherID
        ORDER BY FIELD(sch.schedule_date, 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'), sch.schedule_time
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacherID', $teacherID, PDO::PARAM_INT);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Schedule</title>
    <style>
        /* General Reset */
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
            background-color: #0055a5; /* Dark blue background */
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .logout-button:hover {
            background-color: #003f7f; /* Slightly darker blue on hover */
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
            max-width: 1200px;
            margin: 50px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
          }
          .container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #0033cc;
            font-size: 24px;
            font-weight: bold;
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

.main {
    border: 1px solid red;
    height: 10vh;
    width: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.left {
    border: 1px solid red;
    height: 10vh;
    width: 25%;
}

.right {
    border: 1px solid red;
    height: 10vh;
    width: 25%;
}

/* Schedule Container */
.schedule-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    padding: 20px;
}

/* Schedule Card Styling */
.schedule-card {
    background-color: #ffffff; /* White background for cards */
    border: 1px solid #ddd; /* Light gray border */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    padding: 20px;
    width: 300px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.schedule-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* Slightly stronger shadow on hover */
}

/* Card Heading */
.schedule-card h3 {
    font-size: 1.4em; /* Adjusted font size */
    margin-bottom: 10px;
    color: #333; /* Dark gray for headings */
    font-weight: bold;
}

/* Card Paragraph */
.schedule-card p {
    font-size: 1em;
    margin-bottom: 15px;
    color: #555; /* Medium gray for text */
    text-align: center;
}

/* Manage Button Styling */
.manage-button {
    background-color: #007BFF; /* Blue button */
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    font-size: 1em;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.manage-button:hover {
    background-color: #0056b3; 
    transform: scale(1.05);
}

/* Styling for the Schedule Table */
.schedule-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.schedule-table th, .schedule-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
    font-size: 14px;
}

.schedule-table th {
    background-color: #0033cc; /* Blue header background */
    color: white; /* White text for the header */
    font-weight: bold;
    text-transform: uppercase;
}

.schedule-table tr:nth-child(even) {
    background-color: #f2f2f2; /* Light gray for alternate rows */
}

.schedule-table tr:hover {
    background-color: #e6f7ff; /* Slightly darker gray on hover */
}

.schedule-table td {
    color: #555;
}

/* No Schedule Message */
.container p {
    text-align: center;
    font-size: 16px;
    color: #666;
    margin-top: 20px;
}

/* Card Menu Styling */
.card-menu {
    position: absolute;
    top: 10px; /* Adjust as needed */
    right: 10px; /* Align to the right */
    margin-top: 0; /* Remove the negative margin */
}

.menu-button {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #333;
    padding: 5px;
}

.menu-button:hover {
    color: #007BFF; /* Change color on hover */
}

.dropdown-menu {
    display: none; /* Hidden by default */
    position: absolute;
    right: 0;
    background-color: #ffffff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 5px;
    z-index: 1000;
    min-width: 150px;
    padding: 10px 0;
}

.dropdown-menu a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}

.dropdown-menu a:hover {
    background-color: #f1f1f1;
    color: #007BFF;
}

/* Show the dropdown menu when the button is clicked */
.card-menu:hover .dropdown-menu {
    display: block;
}

/* Card Header Styling */
.card-header {
    display: flex;
    justify-content: space-between; /* Space between the title and menu button */
    align-items: right; /* Align items vertically */
    margin-bottom: 10px; /* Add spacing below the header */
}

/* Show the dropdown menu when the button is clicked */
.card-header:hover .dropdown-menu {
    display: block;
}

/* Parent container of the card-menu */
.card {
    position: relative; /* Make the card the positioning context */
    display: flex;
    justify-content: space-between; /* Space between elements */
    align-items: center; /* Vertically center items */
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
                    <img class="img-home" src="TPC-IMAGES/teacher1-removebg-preview.png" alt="Home Icon" class="home-icon">
                    <span class="home-text">SCHEDULE</span>
                </div>
            </div>  
         </div>
         </header>


     <div class="container">
         <h2>Instructor Schedule</h2>
        <?php if (!empty($schedules)): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Grade</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($schedule['section_name']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['grade']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['schedule_date']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['schedule_time']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['schedule_room']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No schedules assigned to you.</p>
        <?php endif; ?>
     </div>

         

    

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

function manageAttendance(section) {
    alert(`Managing attendance for ${section}`);
    // Redirect or perform other actions here
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
