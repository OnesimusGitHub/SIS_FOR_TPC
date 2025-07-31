<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION["student_id"]) || empty($_SESSION["student_id"])) {
    header("Location: studentLogin.php"); // Redirect to student login page
    exit;
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: studentLogin.php"); // Redirect to login page
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the section, grade level, and strand the student is currently in
    $sql = "
        SELECT s.section_Name, s.shsgrade, st.grade_level
        FROM tblshsstudent st
        INNER JOIN tblshssection s ON st.section_ID = s.section_ID
        WHERE st.shsstud_ID = :student_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $_SESSION["student_id"], PDO::PARAM_STR); // Bind as VARCHAR
    $stmt->execute();
    $section = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure section, grade level, and strand have fallback values
    $sectionName = $section['section_Name'] ?? "No section assigned";
    $gradeLevel = $section['shsgrade'] ?? "N/A";
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Section Teachers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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
            color: #0078D4;
            margin-right: 15px;
        }
        .logout-button {
            background-color: #0033cc;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #0055ff;
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
            background-color: #0033cc;
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
            width: 80px; /* Increased width */
            height: 100px; /* Increased height */
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
        .side-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 270px;
            height: 100%;
            background-color: #0033cc;
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
            background-color: #99bbff; /* Darker blue on hover */
            color: white; /* White text on hover */
            transform: scale(1.05); /* Slight zoom effect */
        }
        .side-menu.open {
            display: block;
            animation: slideIn 0.3s forwards;
        }
        header {
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .header3 {
         /* Changed color to dark green */
        
            color: black;
            background-color:#0056b3;
            color: white;
      padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .teacher-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .teacher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        .teacher-card.math {
            background-color: #e3f2fd; /* Light blue for Math */
        }
        .teacher-card.science {
            background-color: #e8f5e9; /* Light green for Science */
        }
        .teacher-card.english {
            background-color: #fff3e0; /* Light orange for English */
        }
        .teacher-card.history {
            background-color: #f3e5f5; /* Light purple for History */
        }
        .teacher-card.pe {
            background-color: #fbe9e7; /* Light pink for PE */
        }
        .teacher-card.art {
            background-color: #ede7f6; /* Light lavender for Art */
        }
        .teacher-card.other {
            background-color: #ffebee; /* Light red for other fields */
        }
        .schedule-container {
            display: grid; /* Use grid layout for better alignment */
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive columns */
            gap: 20px; /* Space between cards */
            justify-content: center;
            align-items: start;
            text-align: center;
        }
        .schedule-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: #f9f9f9; /* Light background for better readability */
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            background-color: #e6f7ff; /* Subtle highlight on hover */
        }
        .schedule-card.monday {
            background-color: #e3f2fd; /* Light blue for Monday */
        }
        .schedule-card.tuesday {
            background-color: #e8f5e9; /* Light green for Tuesday */
        }
        .schedule-card.wednesday {
            background-color: #fff3e0; /* Light orange for Wednesday */
        }
        .schedule-card.thursday {
            background-color: #f3e5f5; /* Light purple for Thursday */
        }
        .schedule-card.friday {
            background-color: #fbe9e7; /* Light pink for Friday */
        }
        .schedule-card.saturday {
            background-color: #ede7f6; /* Light lavender for Saturday */
        }
        .schedule-card.sunday {
            background-color: #ffebee; /* Light red for Sunday */
        }
        .back-button {
            display: inline-block;
            margin: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
        }

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
            background-color: #99bbff; /* Darker blue on hover */
            color: white; /* White text on hover */
            transform: scale(1.05); /* Slight zoom effect */
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

        .menu-icon {
            cursor: pointer; /* Ensure it's clickable */
            z-index: 1000; /* Bring it above other elements */
            padding: 10px; /* Increase clickable area */
            position: relative; /* Ensure proper positioning */
            display: flex; /* Align lines vertically */
            flex-direction: column;
            justify-content: space-between;
            height: 24px; /* Adjust height for three lines */
            width: 30px; /* Adjust width for the menu icon */
        }

        .menu-icon .line {
            background-color: #fff; /* Ensure visibility (white lines on blue background) */
            height: 3px; /* Thickness of each line */
            width: 100%; /* Full width of the menu icon */
            border-radius: 2px; /* Optional: rounded edges */
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
    <script>
        async function fetchSchedules() {
            try {
                const response = await fetch('fetch_teacher_schedule.php');
                const data = await response.json();

                console.log('Response from fetch_teacher_schedule.php:', data); // Debugging output

                const container = document.querySelector('.schedule-container');
                container.innerHTML = ''; // Clear existing content

                if (!data.success) {
                    container.innerHTML = `<p>${data.message}</p>`;
                    return;
                }

                data.schedules.forEach(schedule => {
                    const card = document.createElement('div');
                    const dayClass = schedule.schedule_date.toLowerCase(); // Convert day to lowercase for class
                    card.className = `schedule-card ${dayClass}`;
                    card.innerHTML = `
                        <h3>${schedule.teachername} ${schedule.teachermidd} ${schedule.teacherlastname}</h3>
                        <p>Field: ${schedule.teacherfield}</p>
                        <p>Time: ${schedule.schedule_time}</p>
                        <p>Date: ${schedule.schedule_date}</p>
                        <p>Room: ${schedule.schedule_room}</p>
                    `;
                    container.appendChild(card);
                });
            } catch (error) {
                console.error('Error fetching schedules:', error);
                const container = document.querySelector('.schedule-container');
                container.innerHTML = '<p>Failed to fetch schedules. Please try again later.</p>';
            }
        }

        async function fetchTeachers() {
            try {
                const response = await fetch('fetch_realtime_teachers.php');
                const data = await response.json();

                console.log('Response from fetch_realtime_teachers.php:', data); // Debugging output

                const container = document.querySelector('.flex-container');
                container.innerHTML = ''; // Clear existing content

                if (!data.success) {
                    container.innerHTML = `<p>${data.message}</p>`;
                    return;
                }

                data.teachers.forEach(teacher => {
                    const card = document.createElement('div');
                    const fieldClass = teacher.teacherfield.toLowerCase().replace(/\s+/g, '') || 'other'; // Normalize field name
                    card.className = `teacher-card ${fieldClass}`;
                    card.innerHTML = `
                        <h3>${teacher.teachername} ${teacher.teachermidd} ${teacher.teacherlastname}</h3>
                        <p>Field: ${teacher.teacherfield}</p>
                        <p>Time: ${teacher.schedule_time || 'N/A'}</p>
                        <p>Date: ${teacher.schedule_date || 'N/A'}</p>
                        <p>Room: ${teacher.schedule_room || 'N/A'}</p>
                    `;
                    container.appendChild(card);
                });
            } catch (error) {
                console.error('Error fetching teachers:', error);
                const container = document.querySelector('.flex-container');
                container.innerHTML = '<p>Failed to fetch teachers. Please try again later.</p>';
            }
        }

        // Fetch schedules and teachers initially and every 1.5 seconds
        fetchSchedules();
        setInterval(fetchSchedules, 1500);
        fetchTeachers();
        setInterval(fetchTeachers, 1500);

        // Burger menu functionality
        document.addEventListener('DOMContentLoaded', function () {
            const burgerMenu = document.getElementById('burger-menu');
            const sideMenu = document.getElementById('side-menu');
            const closeMenu = document.getElementById('close-menu');

            if (burgerMenu && sideMenu && closeMenu) {
                // Open the menu when clicking the burger icon
                burgerMenu.addEventListener('click', function () {
                    sideMenu.classList.add('open'); // Add the 'open' class to make the menu visible
                });

                // Close the menu when clicking the close button
                closeMenu.addEventListener('click', function () {
                    sideMenu.classList.remove('open'); // Remove the 'open' class to hide the menu
                });

                // Close the menu when clicking outside of it
                window.addEventListener('click', function (event) {
                    if (!sideMenu.contains(event.target) && event.target !== burgerMenu) {
                        sideMenu.classList.remove('open');
                    }
                });
            } else {
                console.error('Burger menu elements are missing in the DOM.');
            }
        });

        // Change Password Modal Functionality
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const changePasswordModal = document.getElementById('changePasswordModal');
        const closeModal = document.getElementById('closeModal');

        if (changePasswordBtn && changePasswordModal && closeModal) {
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
        }

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

        // Add smooth scrolling for navigation links
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                const targetId = item.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</head>
<body>
    <header class="main-header">
        <!-- Top Header -->
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Student</span>
                <a href="javascript:void(0);" class="logout-button" onclick="showLogoutModal()">Logout</a>
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
                    <span class="home-text">Schedule</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Side Menu -->
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="studentHome.php" class="menu-item">Home</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="studentPROFILE.php" class="menu-item">Profile</a></li>
            <li><a href="section_teachers.php" class="menu-item">Schedule</a></li>
            <li><a href="studentGrade.php" class="menu-item">Grades</a></li> <!-- Added link to studentGrade.php -->
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
    </script>

    <div class="header3">
        <h1>Teachers in Your Section</h1>
        <h2>Section: <?php echo htmlspecialchars($sectionName); ?> (Grade: <?php echo htmlspecialchars($gradeLevel); ?>)</h2> <!-- Display the student's section and grade level -->
    </div>
   
    <div class="container">
        <h2>Teacher Schedules for Today</h2>
        <div class="schedule-container">
            <!-- Schedule cards will be dynamically inserted here -->
        </div>
    </div>
    <div class="container">
        <h2>Teachers in this Section</h2> <!-- Added header -->
        <div class="flex-container">
            <!-- Teacher cards will be dynamically inserted here -->
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
</body>
</html>
