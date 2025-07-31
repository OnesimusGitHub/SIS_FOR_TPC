<?php
session_start();

// Redirect to login page if the teacher is not logged in
if (!isset($_SESSION["teacherid"]) || empty($_SESSION["teacherid"])) {
    header("Location: teacherLogin.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch announcements
$announcements = [];
$sql = "SELECT announce_content AS content, announcement_ct AS created_at, image_path, type 
        FROM tblannouncementinfo 
        ORDER BY announcement_ct DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="studentPROFILE.css">
    <title>Teacher Announcements</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .main-header {
            border-bottom: 1px solid #ccc;
        }
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
        }
        .logo-section img {
            height: 8vh;
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
            width: 25px;
            height: 3px;
            background-color: white;
            border-radius: 2px;
        }
        .announcement {
            margin: 15px auto;
            padding: 20px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px;
        }
        .announcement h2 {
            font-size: 22px;
            color: #0d47a1;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        .announcement-item {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .announcement-item p {
            margin: 8px 0;
            font-size: 16px;
            color: #424242;
        }
        .announcement-item img {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }
        .announcement-item small {
            display: block;
            margin-top: 10px;
            font-size: 13px;
            color: #757575;
            text-align: right;
        }
    </style>
</head>

<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Admin</span>
                <form action="" method="POST" style="display: inline;">
                    <button type="button" class="logout-button" onclick="showLogoutModal()">Logout</button>
                </form>
            </div>
        </div>
        <br>
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
                <li><a href="studentMAP.php" class="menu-item">Campus Map</a></li>
            </ul>
        </nav>
    </header>

    <div class="announcement">
        <h2>Announcements</h2>
        <div id="announcement-container">
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($announcement['type']); ?></p>
                        <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                        <?php if (!empty($announcement['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" alt="Announcement Image">
                        <?php endif; ?>
                        <small><strong>Posted on:</strong> <?php echo htmlspecialchars($announcement['created_at']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No announcements available.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        const burgerMenu = document.getElementById('burger-menu');
        const sideMenu = document.getElementById('side-menu');
        const closeMenu = document.querySelector('.close-button'); // Updated to select the close button

        // Open the menu when clicking the burger icon
        burgerMenu.addEventListener('click', function () {
            sideMenu.style.display = 'block'; // Show the side menu
        });

        // Close the menu when clicking the close button
        closeMenu.addEventListener('click', function () {
            sideMenu.style.display = 'none'; // Hide the side menu
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
