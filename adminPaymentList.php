<?php
session_start();

// Check if registrar is logged in
if (!isset($_SESSION['registrar_ID']) || empty($_SESSION['registrar_ID'])) {
    header("Location: registrarLogin.php"); // Redirect to registrar login page
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

// Handle search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : ''; // Trim input to remove extra spaces
if ($searchQuery) {
    $stmt = $conn->prepare("
        SELECT p.payment_ID, p.student_id, s.shstud_firstname, s.shstud_lastname, s.student_grade, 
               p.reason, p.custom_reason, p.amount, p.due_date, p.created_at, p.amount_paid, p.paid_date
        FROM tblpayments p
        JOIN tblshsstudent s ON p.student_id = s.shsstud_ID
        WHERE LOWER(s.shsstud_ID) LIKE LOWER(?) OR LOWER(s.shstud_firstname) LIKE LOWER(?) OR LOWER(s.shstud_lastname) LIKE LOWER(?)
        ORDER BY p.created_at DESC
    ");
    $likeQuery = '%' . $searchQuery . '%';
    $stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
} else {
    $stmt = $conn->prepare("
        SELECT p.payment_ID, p.student_id, s.shstud_firstname, s.shstud_lastname, s.student_grade, 
               p.reason, p.custom_reason, p.amount, p.due_date, p.created_at, p.amount_paid, p.paid_date
        FROM tblpayments p
        JOIN tblshsstudent s ON p.student_id = s.shsstud_ID
        ORDER BY p.created_at DESC
    ");
}
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}

// Separate payments into "Paid" and "Not Paid"
$paidPayments = [];
$notPaidPayments = [];

foreach ($payments as $payment) {
    if (!empty($payment['amount_paid']) && !empty($payment['paid_date'])) {
        $paidPayments[] = $payment;
    } else {
        $notPaidPayments[] = $payment;
    }
}

// Separate payments into "Paid" and "Not Paid" by grade
$paidGrade11 = [];
$paidGrade12 = [];
$notPaidGrade11 = [];
$notPaidGrade12 = [];

foreach ($paidPayments as $payment) {
    if ($payment['student_grade'] === 'GRADE 11') {
        $paidGrade11[] = $payment;
    } elseif ($payment['student_grade'] === 'GRADE 12') {
        $paidGrade12[] = $payment;
    }
}

foreach ($notPaidPayments as $payment) {
    if ($payment['student_grade'] === 'GRADE 11') {
        $notPaidGrade11[] = $payment;
    } elseif ($payment['student_grade'] === 'GRADE 12') {
        $notPaidGrade12[] = $payment;
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
    <title>Registrar Payment List</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9fafc;
        }
        header {
            background-color: #0047ab;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .back-button {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #003580;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .search-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .search-bar input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-right: 10px;
            font-size: 14px;
        }
        .search-bar button {
            padding: 10px 20px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .search-bar button:hover {
            background-color: #003580;
        }
        .flex-container {
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }
        .flex-item {
            flex: 1;
            background-color: #f4f4f4;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .flex-item h2 {
            text-align: center;
            color: #333;
        }
        .scrollable-table {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .scrollable-table::-webkit-scrollbar {
            width: 8px;
        }
        .scrollable-table::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 4px;
        }
        .scrollable-table::-webkit-scrollbar-thumb:hover {
            background-color: #aaa;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 6px;
            overflow: hidden;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-records {
            text-align: center;
            color: #555;
            font-size: 1.1rem;
            margin: 20px 0;
        }
        .modal {
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
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            width: 400px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .modal-content iframe {
            width: 100%;
            height: 300px;
            border: none;
        }
        .modal-content button {
            margin-top: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            background-color: #dc3545;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .modal-content button:hover {
            background-color: #c82333;
        }
        .modal-content h2 {
            color: #28a745;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .modal-content button {
            margin-top: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            background-color: #0047ab;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .modal-content button:hover {
            background-color: #003580;
        }
    </style>
    <script>
        function openEditModal(paymentId) {
            const modal = document.getElementById('editModal');
            const iframe = document.getElementById('editIframe');
            iframe.src = `editPayment.php?payment_id=${paymentId}`;
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            const iframe = document.getElementById('editIframe');
            iframe.src = '';
            modal.style.display = 'none';
        }

        function openSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.style.display = 'flex';
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.style.display = 'none';
        }
    </script>
</head>
<body>
    <header>
        <h1>Registrar Payment List</h1>
    </header>
    <div class="container">
        <!-- Back Button -->
        <a href="cashier_dashboard.php" class="back-button">Back to Dashboard</a>

        <!-- Search Bar -->
        <form method="GET" action="" class="search-bar">
            <input type="text" name="search" placeholder="Search by LRN or First Name" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
        </form>

        <div class="flex-container">
            <!-- Paid Students Section -->
            <div class="flex-item">
                <h2>Paid Students - Grade 11</h2>
                <?php if (empty($paidGrade11)): ?>
                    <p class="no-records">No paid records found for Grade 11.</p>
                <?php else: ?>
                    <div class="scrollable-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Student Name</th>
                                    <th>Reason</th>
                                    <th>Amount to Pay</th>
                                    <th>Amount Paid</th>
                                    <th>Paid Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paidGrade11 as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_ID']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['shstud_firstname'] . " " . $payment['shstud_lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['amount_paid']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['paid_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <h2>Paid Students - Grade 12</h2>
                <?php if (empty($paidGrade12)): ?>
                    <p class="no-records">No paid records found for Grade 12.</p>
                <?php else: ?>
                    <div class="scrollable-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Student Name</th>
                                    <th>Reason</th>
                                    <th>Amount to Pay</th>
                                    <th>Amount Paid</th>
                                    <th>Paid Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paidGrade12 as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_ID']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['shstud_firstname'] . " " . $payment['shstud_lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['amount_paid']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['paid_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Not Paid Students Section -->
            <div class="flex-item">
                <h2>Not Paid Students - Grade 11</h2>
                <?php if (empty($notPaidGrade11)): ?>
                    <p class="no-records">All Grade 11 students have paid.</p>
                <?php else: ?>
                    <div class="scrollable-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Student Name</th>
                                    <th>Reason</th>
                                    <th>Amount to Pay</th>
                                    <th>Due Date</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notPaidGrade11 as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_ID']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['shstud_firstname'] . " " . $payment['shstud_lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['due_date']); ?></td>
                                        <td>
                                            <button onclick="openEditModal(<?php echo $payment['payment_ID']; ?>)">Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <h2>Not Paid Students - Grade 12</h2>
                <?php if (empty($notPaidGrade12)): ?>
                    <p class="no-records">All Grade 12 students have paid.</p>
                <?php else: ?>
                    <div class="scrollable-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Student Name</th>
                                    <th>Reason</th>
                                    <th>Amount to Pay</th>
                                    <th>Due Date</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notPaidGrade12 as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_ID']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['shstud_firstname'] . " " . $payment['shstud_lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['due_date']); ?></td>
                                        <td>
                                            <button onclick="openEditModal(<?php echo $payment['payment_ID']; ?>)">Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <iframe id="editIframe"></iframe>
            <button onclick="closeEditModal()">Close</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <h2>Student payment successful</h2>
            <button onclick="closeSuccessModal()">Close</button>
        </div>
    </div>

    <!-- Example usage -->
    <script>
        // Simulate opening the success modal after a payment action
        // Uncomment the line below to test the modal
        // openSuccessModal();
    </script>
</body>
</html>
