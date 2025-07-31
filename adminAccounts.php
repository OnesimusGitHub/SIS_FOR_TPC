<?php
session_start(); // Start the session

// Redirect to login page if the admin is not logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    // Prevent redirection loop by ensuring the current page is not adminLogin.php
    if (basename($_SERVER['PHP_SELF']) !== 'adminLogin.php') {
        header("Location: adminLogin.php"); // Redirect to admin login page
        exit;
    }
}
// Database connection
$db_username = "root";
$db_password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle search query
    $searchQuery = '';
    if (isset($_GET['search'])) {
        $searchQuery = trim($_GET['search']);
        $sql = "SELECT admin_ID, admin_fnam, admin_mname, admin_lname, admin_email, admin_contactno 
                FROM tbladmin 
                WHERE admin_stat IS NULL 
                AND (admin_fnam LIKE :search OR admin_mname LIKE :search OR admin_lname LIKE :search OR admin_email LIKE :search)";
        $stmt = $pdo->prepare($sql);
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->bindParam(':search', $searchTerm);
    } else {
        $sql = "SELECT admin_ID, admin_fnam, admin_mname, admin_lname, admin_email, admin_contactno FROM tbladmin WHERE admin_stat IS NULL";
        $stmt = $pdo->query($sql);
    }

    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle archive request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_admin_id'])) {
        $adminId = $_POST['archive_admin_id'];
        $archiveSql = "UPDATE tbladmin SET admin_stat = 'ARCHIVE' WHERE admin_ID = :adminId";
        $archiveStmt = $pdo->prepare($archiveSql);
        $archiveStmt->bindParam(':adminId', $adminId);
        $archiveStmt->execute();
        header("Location: adminAccounts.php"); // Refresh the page
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: adminLogin.php"); // Redirect to login page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="adminAccounts.css" rel="stylesheet">
    <link rel="stylesheet" href="studentPROFILE.css">
    <title>Admin Accounts</title>
    <style>
        .container {
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-container input[type="text"] {
            width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #0056b3;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .card p {
            margin: 5px 0;
        }

        .menu {
            position: relative;
            display: inline-block;
        }

        .menu button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .menu-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            right: 0;
        }

        .menu:hover .menu-content {
            display: block;
        }

        .menu-content a, .menu-content form {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            display: block;
        }

        .menu-content a:hover {
            background-color: #f1f1f1;
        }

        .menu-content form {
            margin: 0;
        }

        .menu-content form button {
            background: none;
            border: none;
            color: black;
            padding: 8px 16px;
            text-align: left;
            width: 100%;
            cursor: pointer;
        }

        .menu-content form button:hover {
            background-color: #f1f1f1;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .action-buttons button:hover {
            background-color: #5a6268;
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
                <span class="home-text">Manage Admin</span>
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

<div class="container">
    <h1 class="header">Admin Accounts</h1>
    <div class="search-container">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
        </form>
        <div class="action-buttons">
            <button onclick="window.location.href='adminArchivedAccounts.php'">View Archived Admins</button>
           
        </div>
    </div>
    <div class="card-container">
        <?php if (!empty($admins)): ?>
            <?php foreach ($admins as $admin): ?>
                <div class="card">
                    <div class="menu">
                        <button>⋮</button> 
                        <div class="menu-content">
                            <a href="admin_information.php?adminid=<?php echo $admin['admin_ID']; ?>">View Admin Information</a>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to archive this admin?');">
                                <input type="hidden" name="archive_admin_id" value="<?php echo $admin['admin_ID']; ?>">
                                <button type="submit">Archive Admin</button>
                            </form>
                        </div>
                    </div>
                    <h2><?php echo htmlspecialchars($admin['admin_fnam'] . ' ' . $admin['admin_mname'] . ' ' . $admin['admin_lname']); ?></h2>
                    <p>Email: <?php echo htmlspecialchars($admin['admin_email']); ?></p>
                    <p>Contact No: <?php echo htmlspecialchars($admin['admin_contactno']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No admin accounts found.</p>
        <?php endif; ?>
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
</body>
</html>
