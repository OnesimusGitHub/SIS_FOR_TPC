<?php
session_start();

// Check if the registrar is logged in
if (!isset($_SESSION['registrar_ID'])) {
    // Redirect to login page if not logged in
    header("Location: registrarLogin.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: registrarLogin.php");
    exit;
}

// Handle search query
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
if ($searchQuery) {
    $stmt = $conn->prepare("
        SELECT s.shsstud_ID, s.shstud_firstname, s.shstud_lastname, s.shstud_pfp, 
               s.strand_ID, st.strand_name, s.section_ID, s.student_grade 
        FROM tblshsstudent s
        LEFT JOIN tblstrand st ON s.strand_ID = st.strand_ID
        WHERE s.shsstud_ID LIKE ? OR s.shstud_firstname LIKE ? OR s.shstud_lastname LIKE ?");
    $likeQuery = '%' . $searchQuery . '%';
    $stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $allStudents = [];
    while ($row = $result->fetch_assoc()) {
        $allStudents[] = $row;
    }
    $stmt->close();
} else {
    // Fetch all students with strand names
    $allStudents = [];
    $studentResult = $conn->query("
        SELECT s.shsstud_ID, s.shstud_firstname, s.shstud_lastname, s.shstud_pfp, 
               s.strand_ID, st.strand_name, s.section_ID, s.student_grade 
        FROM tblshsstudent s
        LEFT JOIN tblstrand st ON s.strand_ID = st.strand_ID");
    if ($studentResult->num_rows > 0) {
        while ($row = $studentResult->fetch_assoc()) {
            $allStudents[] = $row;
        }
    }
}

// Sort students alphabetically by first name
usort($allStudents, function($a, $b) {
    return strcmp($a['shstud_firstname'], $b['shstud_firstname']);
});

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payment</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
        }
        header {
            background-color: #0047ab;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
        }
        header button {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        header button:hover {
            background-color: #c82333;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .container h2 {
            margin-top: 0;
            color: #333;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .search-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .search-bar input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-right: 10px;
            font-size: 14px;
        }
        .search-bar button {
            padding: 12px 20px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .search-bar button:hover {
            background-color: #003580;
        }
        .flex-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Adjust to fit 4 students per row */
            gap: 20px;
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .flex-container::-webkit-scrollbar {
            width: 8px;
        }
        .flex-container::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 4px;
        }
        .flex-container::-webkit-scrollbar-thumb:hover {
            background-color: #aaa;
        }
        .card {
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .card h3 {
            margin: 10px 0 5px;
            font-size: 1.2rem;
            color: #333;
        }
        .card p {
            margin: 5px 0;
            font-size: 0.9rem;
            color: #555;
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
            padding: 25px;
            border-radius: 12px;
            width: 400px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .modal-content h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .modal-content label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
            color: #555;
        }
        .modal-content input, .modal-content textarea, .modal-content select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .modal-content button {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            background-color: #0047ab;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .modal-content button.close {
            background-color: #dc3545;
        }
        .modal-content button:hover {
            opacity: 0.9;
        }
        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
                gap: 10px;
            }
            .search-bar input[type="text"] {
                margin-right: 0;
            }
            .card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Registrar Payment Management</h1>
        <button onclick="logout()">Logout</button>
    </header>
    <script>
        function logout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "adminPayment.php?logout=true";
            }
        }
    </script>
    <div class="container">
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by ID or Name" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
            <div>
                <button onclick="window.location.href='cashier_dashboard.php'">Go to Dashboard</button>
                <button onclick="window.location.href='adminPaymentList.php'" style="margin-left: 10px;">View All Payments</button>
            </div>
        </div>

        <h2>All Students</h2>
        <button onclick="openAllStudentsModal()" style="margin-bottom: 20px; background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Submit Payment for All Students</button>
        <button onclick="openGradePaymentModal()" style="margin-bottom: 20px; background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Submit Payment by Grade</button>
        <div class="flex-container" id="allStudents">
            <?php if (empty($allStudents)): ?>
                <p>No students found.</p>
            <?php else: ?>
                <?php foreach ($allStudents as $student): ?>
                    <div class="card" id="student-<?php echo htmlspecialchars($student['shsstud_ID']); ?>" onclick="openModal('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($student['shstud_pfp']); ?>" alt="Student Picture">
                        <h3><?php echo htmlspecialchars($student['shstud_firstname'] . " " . $student['shstud_lastname']); ?></h3>
                        <p>Grade: <?php echo htmlspecialchars($student['student_grade'] ?? 'Not Assigned'); ?></p>
                        <p>Strand: <?php echo htmlspecialchars($student['strand_name'] ?? 'Not Assigned'); ?></p>
                        <p>Section: <?php echo htmlspecialchars($student['section_ID'] ?? 'Not Assigned'); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="paymentModal">
        <div class="modal-content">
            <h3>Payment Details</h3>
            <form id="paymentForm" method="POST">
                <input type="hidden" name="student_id" id="studentId">
                <label for="reason">Reason for Payment</label>
                <select name="reason" id="reason" required onchange="toggleCommentBox(this.value)">
                    <option value="" disabled selected>Select a reason</option>
                    <option value="Tuition fee">Tuition fee</option>
                    <option value="Sports fest">Sports fest</option>
                    <option value="Graduation Fee">Graduation Fee</option>
                    <option value="More">More</option>
                </select>
                <div id="commentBox" style="display: none;">
                    <label for="customReason">Please specify:</label>
                    <textarea name="custom_reason" id="customReason"></textarea>
                </div>
                <label for="amount">Amount to Pay</label>
                <input type="number" name="amount" id="amount" required>
                <label for="due_date">Due Date of Payment</label>
                <input type="date" name="due_date" id="due_date" required>
                <button type="button" onclick="submitPayment()">Submit</button>
                <button type="button" class="close" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Modal for All Students -->
    <div class="modal" id="allStudentsPaymentModal">
        <div class="modal-content">
            <h3>Payment Details for All Students</h3>
            <form id="allStudentsPaymentForm" method="POST">
                <label for="reasonAll">Reason for Payment</label>
                <select name="reason" id="reasonAll" required onchange="toggleCommentBoxForAll(this.value)">
                    <option value="" disabled selected>Select a reason</option>
                    <option value="Tuition fee">Tuition fee</option>
                    <option value="Sports fest">Sports fest</option>
                    <option value="Graduation Fee">Graduation Fee</option>
                    <option value="More">More</option>
                </select>
                <div id="commentBoxAll" style="display: none;">
                    <label for="customReasonAll">Please specify:</label>
                    <textarea name="custom_reason" id="customReasonAll"></textarea>
                </div>
                <label for="amountAll">Amount to Pay</label>
                <input type="number" name="amount" id="amountAll" required>
                <label for="due_dateAll">Due Date of Payment</label>
                <input type="date" name="due_date" id="due_dateAll" required>
                <button type="button" onclick="submitPaymentForAll()">Submit</button>
                <button type="button" class="close" onclick="closeAllStudentsModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Modal for Grade Payment -->
    <div class="modal" id="gradePaymentModal">
        <div class="modal-content">
            <h3>Submit Payment by Grade</h3>
            <form id="gradePaymentForm" method="POST">
                <label for="grade">Select Grade:</label>
                <select name="grade" id="grade" required>
                    <option value="" disabled selected>Select a grade</option>
                    <option value="11">Grade 11</option>
                    <option value="12">Grade 12</option>
                </select>
                <label for="reasonGrade">Reason for Payment</label>
                <select name="reason" id="reasonGrade" required onchange="toggleCommentBoxForGrade(this.value)">
                    <option value="" disabled selected>Select a reason</option>
                    <option value="Tuition fee">Tuition fee</option>
                    <option value="Sports fest">Sports fest</option>
                    <option value="Graduation Fee">Graduation Fee</option>
                    <option value="More">More</option>
                </select>
                <div id="commentBoxGrade" style="display: none;">
                    <label for="customReasonGrade">Please specify:</label>
                    <textarea name="custom_reason" id="customReasonGrade"></textarea>
                </div>
                <label for="amountGrade">Amount to Pay</label>
                <input type="number" name="amount" id="amountGrade" required>
                <label for="due_dateGrade">Due Date of Payment</label>
                <input type="date" name="due_date" id="due_dateGrade" required>
                <button type="button" onclick="submitPaymentByGrade()">Submit</button>
                <button type="button" class="close" onclick="closeGradePaymentModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(studentId) {
            document.getElementById('studentId').value = studentId;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        function toggleCommentBox(value) {
            const commentBox = document.getElementById('commentBox');
            commentBox.style.display = value === 'More' ? 'block' : 'none';
        }

        async function submitPayment() {
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('processPayment.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.text();
                alert(result); // Show success or error message
                closeModal(); // Close the modal after submission
            } catch (error) {
                console.error('Error submitting payment:', error);
                alert('An error occurred while submitting the payment.');
            }
        }

        function openAllStudentsModal() {
            document.getElementById('allStudentsPaymentModal').style.display = 'flex';
        }

        function closeAllStudentsModal() {
            document.getElementById('allStudentsPaymentModal').style.display = 'none';
        }

        function toggleCommentBoxForAll(value) {
            const commentBox = document.getElementById('commentBoxAll');
            commentBox.style.display = value === 'More' ? 'block' : 'none';
        }

        async function submitPaymentForAll() {
            const form = document.getElementById('allStudentsPaymentForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('processPaymentForAll.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.text();
                alert(result); // Show success or error message
                closeAllStudentsModal(); // Close the modal after submission
            } catch (error) {
                console.error('Error submitting payment for all students:', error);
                alert('An error occurred while submitting the payment.');
            }
        }

        function openGradePaymentModal() {
            document.getElementById('gradePaymentModal').style.display = 'flex';
        }

        function closeGradePaymentModal() {
            document.getElementById('gradePaymentModal').style.display = 'none';
        }

        function toggleCommentBoxForGrade(value) {
            const commentBox = document.getElementById('commentBoxGrade');
            commentBox.style.display = value === 'More' ? 'block' : 'none';
        }

        async function submitPaymentByGrade() {
            const form = document.getElementById('gradePaymentForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('processPaymentByGrade.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.text();
                alert(result); // Show success or error message
                closeGradePaymentModal(); // Close the modal after submission
            } catch (error) {
                console.error('Error submitting payment by grade:', error);
                alert('An error occurred while submitting the payment.');
            }
        }
    </script>
</body>
</html>
