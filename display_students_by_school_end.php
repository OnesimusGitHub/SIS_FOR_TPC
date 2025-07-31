<?php
// Suppress errors and warnings to prevent breaking JSON responses
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    // Redirect to admin login page if not logged in
    header("Location: adminLogin.php");
    exit;
}

// Fetch students whose studstat is set to ARCHIVED
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

// Fetch students with studstat set to ARCHIVED or school_end date in the past
$sql = "SELECT shsstud_ID, shstud_firstname, shstud_lastname, school_year, school_end 
        FROM tblshsstudent 
        WHERE studstat = 'ARCHIVED' OR school_end < CURDATE()";
$result = $conn->query($sql);

$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Students or Expired School Year</title>
    <link rel="stylesheet" href="manageStudent.css">
    <link rel="stylesheet" href="studentPROFILE.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f4f4f9;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            max-height: 500px; /* Set a maximum height for the container */
            overflow-y: auto; /* Enable vertical scrolling */
            padding: 10px; /* Add padding inside the container */
            background-color: #f9f9f9; /* Light background color */
            border: 1px solid #ddd; /* Border color */
            border-radius: 8px; /* Rounded corners */
            margin: auto;
            width: 1180px;
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
        .student-card {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .student-card h3 {
            margin: 10px 0;
            font-size: 1.4rem;
            color: #1f2937;
            font-weight: bold;
        }
        .student-card p {
            margin: 5px 0;
            font-size: 1rem;
            color: #4b5563;
        }
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        .back-button {
            text-align: right;
            margin-bottom: 20px;
        }
        .back-button button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-button button:hover {
            background-color: #0056b3;
        }
        .modify-button {
            padding: 8px 15px;
            background-color: #ffc107;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .modify-button:hover {
            background-color: #e0a800;
        }
        .restore-button {
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .restore-button:hover {
            background-color: #218838;
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
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            cursor: pointer;
        }
        .close-button:hover {
            color: red;
        }
        .modal form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .modal form label {
            font-size: 14px;
            color: #333;
        }
        .modal form input {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .modal form button {
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .modal form button:hover {
            background-color: #218838;
        }
        .confirmation-modal {
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

        .confirmation-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        .confirmation-modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .confirmation-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .confirmation-modal-content .confirm-restore {
            background-color: #28a745;
            color: white;
        }

        .confirmation-modal-content .confirm-restore:hover {
            background-color: #218838;
        }

        .confirmation-modal-content .cancel-restore {
            background-color: #f2f2f2;
            color: #333;
        }

        .confirmation-modal-content .cancel-restore:hover {
            background-color: #e0e0e0;
        }
        .update-modal {
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

        .update-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 400px;
        }

        .update-modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .update-modal-content label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #555;
        }

        .update-modal-content input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .update-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .update-modal-content .save-update {
            background-color: #007bff;
            color: white;
        }

        .update-modal-content .save-update:hover {
            background-color: #0056b3;
        }

        .update-modal-content .cancel-update {
            background-color: #f2f2f2;
            color: #333;
        }

        .update-modal-content .cancel-update:hover {
            background-color: #e0e0e0;
        }
        .notification-modal {
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

        .notification-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        .notification-modal-content p {
            margin-bottom: 20px;
            font-size: 1rem;
            color: #333;
        }

        .notification-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .notification-modal-content .close-notification {
            background-color: #007bff;
            color: white;
        }

        .notification-modal-content .close-notification:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<header class="main-header">
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Admin</span>
                <button class="logout-button" onclick="logout()">Logout</button>
            </div>
        </div>
        <br>
       
    </header>
<body>
    <h1 style="margin-top: 50px;">Archived Students or Expired School Year</h1>
    <div class="back-button">
        <button onclick="window.location.href='manageStudent.php'">Back to Manage Students</button>
    </div>

    <?php if (empty($students)): ?>
        <p style="text-align: center; color: gray;">No archived students or students with expired school year found.</p>
    <?php else: ?>
        <div class="card-container">
            <?php foreach ($students as $student): ?>
                <div class="student-card">
                    <h3><?php echo htmlspecialchars($student['shstud_firstname'] . " " . $student['shstud_lastname']); ?></h3>
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['shsstud_ID']); ?></p>
                    <p><strong>School Year:</strong> 
                        <?php 
                        echo htmlspecialchars($student['school_year'] ?? 'Not Assigned'); 
                        ?>
                    </p>
                    <p><strong>School End:</strong> 
                        <?php 
                        echo $student['school_end'] ? htmlspecialchars(date('F j, Y', strtotime($student['school_end']))) : 'Not Assigned'; 
                        ?>
                    </p>
                    <?php if ($student['school_end'] && strtotime($student['school_end']) < time()): ?>
                        <button class="modify-button" onclick="openUpdateModal('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">Update School Year</button>
                    <?php else: ?>
                        <button class="restore-button" onclick="restoreStudent('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">Restore Student</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modal -->
    <div id="updateModal" class="update-modal">
        <div class="update-modal-content">
            <h3>Update School Year</h3>
            <form id="updateForm">
                <input type="hidden" id="studentId" name="studentId">
                <label for="schoolYear">Start of School Year:</label>
                <input type="date" id="schoolYear" name="schoolYear" required>
                <label for="schoolEnd">End of School Year:</label>
                <input type="date" id="schoolEnd" name="schoolEnd" required>
                <button type="button" class="save-update" onclick="updateSchoolYear()">Save Changes</button>
                <button type="button" class="cancel-update" onclick="closeUpdateModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div id="restoreModal" class="confirmation-modal">
        <div class="confirmation-modal-content">
            <h3>Are you sure you want to restore this student?</h3>
            <button id="confirmRestoreButton" class="confirm-restore">Yes</button>
            <button class="cancel-restore" onclick="closeRestoreModal()">No</button>
        </div>
    </div>

    <div id="notificationModal" class="notification-modal">
        <div class="notification-modal-content">
            <p id="notificationMessage"></p>
            <button class="close-notification" onclick="closeNotificationModal()">Close</button>
        </div>
    </div>

    <script>
        function openUpdateModal(studentId) {
            document.getElementById('studentId').value = studentId;
            document.getElementById('updateModal').style.display = 'flex';
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        async function updateSchoolYear() {
            const studentId = document.getElementById('studentId').value;
            const schoolYear = document.getElementById('schoolYear').value;
            const schoolEnd = document.getElementById('schoolEnd').value;

            if (!schoolYear || !schoolEnd) {
                alert('Please fill in both dates.');
                return;
            }

            try {
                const response = await fetch('update_school_year.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ studentId, schoolYear, schoolEnd })
                });

                const result = await response.json();
                if (result.success) {
                    alert(result.message || "School year updated successfully!");
                    location.reload(); // Reload the page to update the list
                } else {
                    alert(result.message || "Failed to update school year.");
                }
            } catch (error) {
                console.error("Error updating school year:", error);
                alert("An error occurred while updating the school year.");
            } finally {
                closeUpdateModal();
            }
        }

        let studentToRestore = null;

        function showRestoreModal(studentId) {
            studentToRestore = studentId;
            document.getElementById('restoreModal').style.display = 'flex';
        }

        function closeRestoreModal() {
            studentToRestore = null;
            document.getElementById('restoreModal').style.display = 'none';
        }

        function showNotificationModal(message) {
            document.getElementById('notificationMessage').textContent = message;
            const modal = document.getElementById('notificationModal');
            modal.style.display = 'flex';

            // Automatically close the modal after 3 seconds
            setTimeout(() => {
                modal.style.display = 'none';
            }, 3000);
        }

        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        document.getElementById('confirmRestoreButton').addEventListener('click', async () => {
            if (!studentToRestore) return;

            try {
                const response = await fetch('restore_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ studentId: studentToRestore })
                });

                const result = await response.json();
                if (result.success) {
                    showNotificationModal(result.message || "Student restored successfully!");
                    location.reload(); // Reload the page to update the list
                } else {
                    showNotificationModal(result.message || "Failed to restore student.");
                }
            } catch (error) {
                console.error("Error restoring student:", error);
                showNotificationModal("An error occurred while restoring the student.");
            } finally {
                closeRestoreModal();
            }
        });

        async function restoreStudent(studentId) {
            showRestoreModal(studentId);
        }

        function logout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "manageStudent.php?logout=true";
            }
        }
    </script>
</body>
</html>
