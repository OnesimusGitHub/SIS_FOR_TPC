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
        $sql = "SELECT registrar_ID, registrar_fname, registrar_mname, registrar_lname, registrar_email, registrar_contactno 
                FROM tblregistrar 
                WHERE registrar_stat = 'ARCHIVE' 
                AND (registrar_fname LIKE :search OR registrar_mname LIKE :search OR registrar_lname LIKE :search OR registrar_email LIKE :search)";
        $stmt = $pdo->prepare($sql);
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->bindParam(':search', $searchTerm);
    } else {
        $sql = "SELECT registrar_ID, registrar_fname, registrar_mname, registrar_lname, registrar_email, registrar_contactno FROM tblregistrar WHERE registrar_stat = 'ARCHIVE'";
        $stmt = $pdo->query($sql);
    }

    $stmt->execute();
    $archivedRegistrars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle restore request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_registrar_id'])) {
        $registrarId = $_POST['restore_registrar_id'];
        $restoreSql = "UPDATE tblregistrar SET registrar_stat = NULL WHERE registrar_ID = :registrarId";
        $restoreStmt = $pdo->prepare($restoreSql);
        $restoreStmt->bindParam(':registrarId', $registrarId);
        $restoreStmt->execute();
        header("Location: registrarArchivedAccounts.php"); // Refresh the page
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="registrarAccounts.css" rel="stylesheet">
    <title>Archived Registrar Accounts</title>
    <style>
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            max-height: 500px; /* Set a maximum height for the container */
            overflow-y: auto; /* Enable vertical scrolling */
            padding: 20px; /* Add padding inside the container */
            background-color: #f9f9f9; /* Light background color */
            border: 1px solid #ddd; /* Border color */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add box shadow */
        }

        .card-container::-webkit-scrollbar {
            width: 8px;
        }

        .card-container::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 4px;
        }

        .card-container::-webkit-scrollbar-thumb:hover {
            background-color: #aaa;
        }

        .card {
            position: relative;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: left;
            margin: 10px;
        }

        .restore-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .restore-btn:hover {
            background-color: #218838;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px; /* Add spacing between elements */
        }

        .search-container input[type="text"] {
            flex: 1; /* Allow the input to take up available space */
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

        .action-buttons {
            display: flex;
            gap: 10px; /* Add spacing between buttons */
        }

        .back-btn, .dashboard-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .back-btn:hover, .dashboard-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Archived Registrar Accounts</h1>
        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
            <div class="action-buttons">
                <button class="back-btn" onclick="window.location.href='registrarAccounts.php'">Back to Registrar Accounts</button>
                <button class="dashboard-btn" onclick="window.location.href='admin_dashboard.php'">Back to Dashboard</button>
            </div>
        </div>
        <div class="card-container">
            <?php if (!empty($archivedRegistrars)): ?>
                <?php foreach ($archivedRegistrars as $registrar): ?>
                    <div class="card">
                        <h2><?php echo htmlspecialchars($registrar['registrar_fname'] . ' ' . $registrar['registrar_mname'] . ' ' . $registrar['registrar_lname']); ?></h2>
                        <p>Email: <?php echo htmlspecialchars($registrar['registrar_email']); ?></p>
                        <p>Contact No: <?php echo htmlspecialchars($registrar['registrar_contactno']); ?></p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to restore this registrar?');">
                            <input type="hidden" name="restore_registrar_id" value="<?php echo htmlspecialchars($registrar['registrar_ID']); ?>">
                            <button type="submit" class="restore-btn">Restore</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No archived registrar accounts found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
