<?php
session_start();

if (!isset($_SESSION["registrar_ID"]) || empty($_SESSION["registrar_ID"])) { // Check if registrar_ID is set and not empty
    header("Location: registrarLogin.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: registrarLogin.php'); // Redirect to login page
    exit();
}

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT registrar_ID, registrar_fname, registrar_lname FROM tblregistrar WHERE registrar_ID = :registrarid";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':registrarid', $_SESSION["registrar_ID"]);
    $stmt->execute();
    $registrar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registrar) {
        die("Error: Registrar not found. Please contact the administrator.");
    }
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="studentPROFILE.css">
    <style>
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background-color: #f1f5f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }
        .card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #333;
        }
        .card a {
            display: block;
            margin-top: 15px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .card a:hover {
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
                <span class="user-role">Registrar</span>
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
                    <span class="home-text">Dashboard</span>
                </div>
            </div>
        </div>
    </header>
    <div class="container">
        <h1 class="section-title">Welcome, <?php echo htmlspecialchars($registrar["registrar_fname"] . " " . $registrar["registrar_lname"]); ?>!</h1>
        <div class="flex-container">
            <div class="card">
                <h3>Manage Payment</h3>
                <a href="adminPayment.php">Go to Manage Payment</a>
            </div>
            <div class="card">
                <h3>Payment List</h3>
                <a href="adminPaymentList.php">Go to Payment List</a>
            </div>
        </div>
    </div>



    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="registrar_dashboard.php" class="menu-item">Dashboard</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="registrarPROFILE.php" class="menu-item">Profile</a></li>
        
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
        // Toggle the side menu when clicking the burger icon
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
