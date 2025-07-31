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

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: adminLogin.php"); // Redirect to login page
    exit();
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
        $sql = "SELECT registrar_ID, registrar_fname, registrar_mname, registrar_lname, registrar_email, registrar_contactno 
                FROM tblregistrar 
                WHERE registrar_stat IS NULL 
                AND (registrar_fname LIKE :search OR registrar_mname LIKE :search OR registrar_lname LIKE :search OR registrar_email LIKE :search)";
        $stmt = $pdo->prepare($sql);
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->bindParam(':search', $searchTerm);
    } else {
        $sql = "SELECT registrar_ID, registrar_fname, registrar_mname, registrar_lname, registrar_email, registrar_contactno FROM tblregistrar WHERE registrar_stat IS NULL";
        $stmt = $pdo->query($sql);
    }

    $stmt->execute();
    $registrars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_cashier_id'])) {
    $cashierId = $_POST['archive_cashier_id'];

    try {
        $archiveSql = "UPDATE tblregistrar SET cashier_stat = 'ARCHIVE' WHERE registrar_ID = :cashierId";
        $archiveStmt = $pdo->prepare($archiveSql);
        $archiveStmt->bindParam(':cashierId', $cashierId);
        $archiveStmt->execute();
        echo '<script>alert("Cashier archived successfully!"); window.location.reload();</script>';
    } catch (PDOException $e) {
        die("Error archiving cashier: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="registrarAccounts.css" rel="stylesheet">
    <link rel="stylesheet" href="studentPROFILE.css">
    <title>Registrar Accounts</title>
    <style>
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

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
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
            flex-wrap: wrap;
            gap: 10px;
        }

        .search-container input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .search-container button:hover {
            background-color: #0056b3;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card {
            position: relative;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .card p {
            margin: 5px 0;
            font-size: 0.9rem;
            color: #555;
        }

        .menu {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .menu-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            right: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .menu-content a, .menu-content form button {
            color: black;
            padding: 10px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .menu-content a:hover, .menu-content form button:hover {
            background-color: #f1f1f1;
        }

        .menu:hover .menu-content {
            display: block;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-buttons button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .action-buttons button:hover {
            background-color: #5a6268;
        }

        .archived-btn {
            background-color: #007bff;
        }

        .archived-btn:hover {
            background-color: #0056b3;
        }

        .dashboard-btn {
            background-color: #6c757d;
        }

        .dashboard-btn:hover {
            background-color: #5a6268;
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
                <span class="home-text">Manage Cashier</span>
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

<div class="container">
    <h1>Cashier Accounts</h1>
    <div class="search-container">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
        </form>
        <div class="action-buttons">
            <button class="archived-btn" onclick="window.location.href='registrarArchivedAccounts.php'">View Archived Registrars</button>
            
        </div>
    </div>
    <div class="card-container">
        <?php if (!empty($registrars)): ?>
            <?php foreach ($registrars as $registrar): ?>
                <div class="card">
                    <div class="menu">
                        <span>⋮</span>
                        <div class="menu-content">
                            <a href="admin_registrar_information.php?registrarid=<?php echo htmlspecialchars($registrar['registrar_ID']); ?>">View Cashier Information</a>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="archive_cashier_id" value="<?php echo htmlspecialchars($registrar['registrar_ID']); ?>">
                                <button type="submit">Archive Cashier</button>
                            </form>
                        </div>
                    </div>
                    <h2><?php echo htmlspecialchars($registrar['registrar_fname'] . ' ' . $registrar['registrar_mname'] . ' ' . $registrar['registrar_lname']); ?></h2>
                    <p>Email: <?php echo htmlspecialchars($registrar['registrar_email']); ?></p>
                    <p>Contact No: <?php echo htmlspecialchars($registrar['registrar_contactno']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No registrar accounts found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>


