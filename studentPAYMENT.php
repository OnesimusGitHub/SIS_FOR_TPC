<?php
session_start();

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

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: studentLogin.php"); // Redirect to login page
    exit();
}

// Assuming the logged-in student's ID is stored in the session
if (!isset($_SESSION['student_id'])) {
    die("Error: Student not logged in.");
}

$student_id = $_SESSION['student_id'];

// Fetch payment details for the logged-in student
$stmt = $conn->prepare("SELECT reason, custom_reason, amount, due_date, created_at, amount_paid, paid_date FROM tblpayments WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$paidPayments = [];
$unpaidPayments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['amount_paid']) && !empty($row['paid_date'])) {
            $paidPayments[] = $row;
        } else {
            $unpaidPayments[] = $row;
        }
    }
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Payments</title>
    
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
            background-color: #cce0ff;
            color: #003366;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .side-menu ul li a:hover {
            background-color: #b3ccff;
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
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .container h2 {
            margin-top: 0;
            color: #333;
        }
        .back-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-button:hover {
            background-color: #003580;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .no-records {
            text-align: center;
            color: #555;
            font-size: 1.1rem;
            margin: 20px 0;
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
        /* Enhanced UI styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            font-size: 1.8rem;
            color: #0047ab;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fdfdfd;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #0047ab;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-records {
            text-align: center;
            color: #777;
            font-size: 1.2rem;
            margin: 20px 0;
        }
        .back-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #003580;
        }
        .logout-button {
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .logout-button:hover {
            background-color: #003580;
        }
        .logout-modal-content {
            width: 350px;
        }
        .logout-modal-content button {
            padding: 10px 25px;
        }
    </style>
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
                    <span class="home-text">Payment</span>
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
            <li><a href="studentGRADE.php" class="menu-item">Grades</a></li> <!-- Added Grades menu item -->
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
       

        <h2>Paid Payments</h2>
        <?php if (empty($paidPayments)): ?>
            <p class="no-records">No paid payments found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Reason</th>
                        <th>Custom Reason</th>
                        <th>Amount</th>
                        <th>Paid Date</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paidPayments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                            <td><?php echo htmlspecialchars($payment['custom_reason'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($payment['amount_paid']); ?></td>
                            <td><?php echo htmlspecialchars($payment['paid_date']); ?></td>
                            <td><?php echo htmlspecialchars($payment['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Unpaid Payments</h2>
        <?php if (empty($unpaidPayments)): ?>
            <p class="no-records">All payments have been made.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Reason</th>
                        <th>Custom Reason</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unpaidPayments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                            <td><?php echo htmlspecialchars($payment['custom_reason'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                            <td><?php echo htmlspecialchars($payment['due_date']); ?></td>
                            <td><?php echo htmlspecialchars($payment['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>