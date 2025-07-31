<?php
session_start();

// Check if the registrar is logged in
if (!isset($_SESSION['registrar_ID'])) {
    http_response_code(403);
    echo "Unauthorized access.";
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
    http_response_code(500);
    echo "Database connection failed: " . $conn->connect_error;
    exit();
}

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = isset($_POST['grade']) ? trim($_POST['grade']) : null;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;
    $customReason = isset($_POST['custom_reason']) ? trim($_POST['custom_reason']) : null;
    $amount = isset($_POST['amount']) ? trim($_POST['amount']) : null;
    $dueDate = isset($_POST['due_date']) ? trim($_POST['due_date']) : null;

    if (!$grade || !$reason || !$amount || !$dueDate) {
        http_response_code(400);
        echo "All fields are required.";
        exit();
    }

    // Use custom reason if provided
    $finalReason = ($reason === 'More' && $customReason) ? $customReason : $reason;

    // Debugging: Log the grade being searched
    error_log("Searching for students in Grade: $grade");

    // Fetch all students in the selected grade
    $stmt = $conn->prepare("
        SELECT shsstud_ID 
        FROM tblshsstudent 
        WHERE student_grade = ? OR student_grade = ?");
    $gradeWithText = "Grade " . $grade; // Fallback for mismatched values
    $stmt->bind_param("ss", $grade, $gradeWithText);
    $stmt->execute();
    $result = $stmt->get_result();

    // Debugging: Log the number of rows found
    error_log("Number of students found in Grade $grade: " . $result->num_rows);

    if ($result->num_rows > 0) {
        $conn->begin_transaction();
        try {
            while ($row = $result->fetch_assoc()) {
                $studentId = $row['shsstud_ID'];

                // Insert payment record for each student
                $paymentStmt = $conn->prepare("
                    INSERT INTO tblpayments (student_id, reason, amount, due_date, created_at) 
                    VALUES (?, ?, ?, ?, NOW())");
                $paymentStmt->bind_param("ssds", $studentId, $finalReason, $amount, $dueDate);
                $paymentStmt->execute();
                $paymentStmt->close();
            }
            $conn->commit();
            echo "Payment successfully assigned to all Grade $grade students.";
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo "An error occurred while processing payments: " . $e->getMessage();
        }
    } else {
        // Debugging: Log if no students are found
        error_log("No students found in Grade: $grade. Check the database values for student_grade.");
        echo "No students found in Grade $grade.";
    }

    $stmt->close();
} else {
    http_response_code(405);
    echo "Invalid request method.";
}

$conn->close();
?>
