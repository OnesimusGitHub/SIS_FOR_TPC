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

// Check if payment_id is provided
if (!isset($_GET['payment_id'])) {
    die("Error: Payment ID not provided.");
}

$payment_id = $_GET['payment_id'];

// Fetch payment details
$stmt = $conn->prepare("SELECT payment_ID, amount_paid, paid_date FROM tblpayments WHERE payment_ID = ?");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Payment record not found.");
}

$payment = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount_paid = $_POST['amount_paid'];
    $paid_date = $_POST['paid_date'];

    $updateStmt = $conn->prepare("UPDATE tblpayments SET amount_paid = ?, paid_date = ? WHERE payment_ID = ?");
    $updateStmt->bind_param("dsi", $amount_paid, $paid_date, $payment_id);

    if ($updateStmt->execute()) {
        header("Location: adminPaymentList.php?success=Payment updated successfully");
        exit();
    } else {
        $error = "Error updating payment: " . $updateStmt->error;
    }

    $updateStmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment</title>
    <style>
        form {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        button.cancel {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <h1>Edit Payment</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="amount_paid">Amount Paid</label>
        <input type="number" name="amount_paid" id="amount_paid" value="<?php echo htmlspecialchars($payment['amount_paid'] ?? ''); ?>" required>

        <label for="paid_date">Paid Date</label>
        <input type="date" name="paid_date" id="paid_date" value="<?php echo htmlspecialchars($payment['paid_date'] ?? ''); ?>" required>

        <button type="submit">Save Changes</button>
        <button type="button" class="cancel" onclick="window.location.href='adminPaymentList.php'">Cancel</button>
    </form>
</body>
</html>
