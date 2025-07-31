<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    // Prevent redirection loop by ensuring the current page is not adminLogin.php
    if (basename($_SERVER['PHP_SELF']) !== 'adminLogin.php') {
        header("Location: adminLogin.php"); // Redirect to admin login page
        exit;
    }
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: adminLogin.php"); // Redirect to login page
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for adding announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $announcement = $_POST['announcement'] ?? '';
    $type = $_POST['type'] ?? 'General';
    $imagePath = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        
        // Ensure the uploads directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create the directory with write permissions
        }

        $imagePath = $uploadDir . time() . '_' . basename($_FILES['image']['name']); // Add a timestamp to avoid overwriting
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            echo "<script>alert('Failed to upload image.');</script>";
        }
    }

    $stmt = $conn->prepare("INSERT INTO tblannouncementinfo (announce_content, type, image_path) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $announcement, $type, $imagePath);
    if ($stmt->execute()) {
        echo "<script>alert('Announcement added successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle form submission for editing announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement'])) {
    $id = $_POST['id'];
    $content = $_POST['content'];
    $stmt = $conn->prepare("UPDATE tblannouncementinfo SET announce_content = ? WHERE announce_ID = ?");
    $stmt->bind_param("si", $content, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Announcement updated successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle deletion of announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM tblannouncementinfo WHERE announce_ID = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Announcement deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle deletion of all announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_announcements'])) {
    $stmt = $conn->prepare("DELETE FROM tblannouncementinfo");
    if ($stmt->execute()) {
        echo "<script>alert('All announcements deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all announcements for admin view
$adminAnnouncements = [];
$result = $conn->query("SELECT announce_ID, announce_content, type, image_path, DATE_FORMAT(announcement_ct, '%M %d, %Y %h:%i %p') AS announcement_ct FROM tblannouncementinfo ORDER BY announce_ID DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $adminAnnouncements[] = $row;
    }
}

// Debugging: Log session variables to ensure they are set correctly
error_log("Session student_id: " . ($_SESSION["student_id"] ?? "Not Set"));
error_log("Session teacherid: " . ($_SESSION["teacherid"] ?? "Not Set"));

// Fetch all announcements (no filtering by recipient)
$allAnnouncements = [];
$result = $conn->query("SELECT announce_content, type, image_path, DATE_FORMAT(announcement_ct, '%M %d, %Y %h:%i %p') AS announcement_ct 
                        FROM tblannouncementinfo 
                        ORDER BY announce_ID DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allAnnouncements[] = $row;
    }
} else {
    error_log("Error fetching announcements: " . $conn->error);
}

// Debugging: Log fetched announcements
error_log("All Announcements: " . json_encode($allAnnouncements));

// Debugging: Log all announcements for admin view
error_log("Admin Announcements: " . json_encode($adminAnnouncements));

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="adminHome.css" rel="stylesheet">
    <link rel="stylesheet" href="studentPROFILE.css">
    <title>Admin Home</title>
    <style>
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

        .menu-section-title {
            font-size: 14px;
            text-transform: uppercase;
            margin: 10px 0;
            color: #ccc;
        }

        .navButton {
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
        }

        .navButton:hover {
            background-color: #0056b3;
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
<header class="main-header">
<div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Admin</span>
                <button class="logout-button" onclick="showLogoutModal()">Logout</button>
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
</header>

<!-- Side Menu -->
<nav class="side-menu" id="side-menu">
    <button class="close-button" id="close-menu">&times;</button>
    <ul>
    <li class="menu-section-title">MY</li>
            <li><a href="admin_dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="adminPROFILE.php" class="menu-item">Profile</a></li>
            <li class="menu-section-title">ADD ACCOUNTS</li>
            <li><a href="admin_student_addSHS.php" class="menu-item">Add SHS Student</a></li>
            <li><a href="admin_instructor_add.php" class="menu-item">Add Instructor</a></li>
            <li><a href="admin_addAdmin.php" class="menu-item">Add Admin</a></li>
            <li><a href="admin_cashier_add.php" class="menu-item">Add Cashier</a></li>
            <li class="menu-section-title">ACCOUNTS</li>
            <li><a href="manageStudent.php" class="menu-item">Manage Students</a></li>
            <li><a href="adminAccounts.php" class="menu-item">Manage Admins</a></li>
            <li><a href="registrarAccounts.php" class="menu-item">Manage Cashiers</a></li>
            <li class="menu-section-title">ANNOUNCEMENTS</li>
            <li><a href="adminHome.php" class="menu-item">Announcements</a></li>
            <li class="menu-section-title">ANALYTICS</li>
            <li><a href="shonget.php" class="menu-item">Analytics</a></li>
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

    function showLogoutModal() {
        document.getElementById('logoutModal').style.display = 'flex';
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }
</script>

<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <h3>Are you sure you want to log out?</h3>
        <form action="" method="GET" style="display: inline;">
            <button type="submit" name="logout" value="true" class="confirm-logout">Yes</button>
        </form>
        <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
    </div>
</div>

<div class="admin-form">
    <form method="POST" enctype="multipart/form-data">
        <label for="announcement">Enter Announcement:</label><br>
        <textarea id="announcement" name="announcement" rows="4" cols="50" placeholder="Enter your announcement here..."></textarea><br>
        <label for="type">Select Type:</label><br>
        <select id="type" name="type">
            <option value="General">General</option>
            <option value="Important">Important</option>
            <option value="Incoming Event">Incoming Event</option>
        </select><br><br>
        <label for="image">Upload Image:</label><br>
        <input type="file" id="image" name="image" accept="image/*"><br><br>
        <button type="submit" name="add_announcement">Submit Announcement</button>
    </form>
</div>

<div class="admin-announcements">
    <h2>Your Announcements</h2>
    <form method="POST" style="margin-bottom: 20px;">
        <button type="submit" name="delete_all_announcements" class="delete-all-button" onclick="return confirm('Are you sure you want to delete all announcements?');">Delete All Announcements</button>
    </form>
    <?php if (!empty($adminAnnouncements)): ?>
        <ul class="announcement-list">
            <?php foreach ($adminAnnouncements as $announcement): ?>
                <li class="announcement-card">
                    <form method="POST" class="announcement-form">
                        <input type="hidden" name="id" value="<?php echo $announcement['announce_ID']; ?>">
                        <textarea name="content" rows="2" cols="50" class="announcement-textarea"><?php echo htmlspecialchars($announcement['announce_content']); ?></textarea><br>
                        <p>Type: <?php echo htmlspecialchars($announcement['type']); ?></p>
                        <?php if (!empty($announcement['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" alt="Announcement Image" class="announcement-image"><br>
                        <?php endif; ?>
                        <small class="announcement-time">Posted on: <?php echo htmlspecialchars($announcement['announcement_ct']); ?></small><br>
                        <button type="submit" name="edit_announcement" class="edit-button">Edit</button>
                        <button type="submit" name="delete_announcement" class="delete-button" onclick="return confirm('Are you sure you want to delete this announcement?');">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No announcements added yet.</p>
    <?php endif; ?>
</div>
</body>
</html>