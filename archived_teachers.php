<?php
require 'db_connection.php'; // Include your database connection

session_start();

// Restrict access to admin only
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: adminLogin.php'); // Redirect to admin login page
    exit();
}

$sql = "SELECT * FROM teachrinf WHERE teachstat = 'ARCHIVED'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Teachers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
        }
        h1 {
            margin: 20px 0;
            text-align: center;
            color: #007bff;
        }
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #007bff;
            color: white;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        table tr:hover {
            background-color: #e9ecef;
        }
        .restore-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .restore-button:hover {
            background-color: #218838;
        }
        .back-button {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px 15px;
            text-align: center;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .restore-modal {
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

        .restore-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        .restore-modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .restore-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .restore-modal-content .confirm-restore {
            background-color: #28a745;
            color: white;
        }

        .restore-modal-content .confirm-restore:hover {
            background-color: #218838;
        }

        .restore-modal-content .cancel-restore {
            background-color: #f2f2f2;
            color: #333;
        }

        .restore-modal-content .cancel-restore:hover {
            background-color: #e0e0e0;
        }
    </style>
    <script>
        let teacherToRestore = null;

        function showRestoreModal(teacherId) {
            teacherToRestore = teacherId;
            document.getElementById('restoreModal').style.display = 'flex';
        }

        function closeRestoreModal() {
            teacherToRestore = null;
            document.getElementById('restoreModal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const confirmRestoreButton = document.getElementById('confirmRestoreButton');
            confirmRestoreButton.addEventListener('click', () => {
                if (!teacherToRestore) return;

                fetch('restore_teacher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ teacherid: teacherToRestore })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert(data.message); // Show error message only if restoration fails
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while restoring the teacher.');
                })
                .finally(() => {
                    closeRestoreModal();
                });
            });
        });
    </script>
</head>
<body>
    <header>
        <h1>Archived Teachers</h1>
    </header>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Field</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['teacherid']); ?></td>
                    <td><?php echo htmlspecialchars($row['teachername'] . " " . $row['teachermidd'] . " " . $row['teacherlastname']); ?></td>
                    <td><?php echo htmlspecialchars($row['teacherfield']); ?></td>
                    <td><?php echo htmlspecialchars($row['teachstat']); ?></td>
                    <td>
                        <button class="restore-button" onclick="showRestoreModal(<?php echo htmlspecialchars($row['teacherid']); ?>)">Restore</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="admin_dashboard.php" class="back-button">Back to Dashboard</a>

    <div id="restoreModal" class="restore-modal">
        <div class="restore-modal-content">
            <h3>Are you sure you want to restore this teacher?</h3>
            <button id="confirmRestoreButton" class="confirm-restore">Yes</button>
            <button class="cancel-restore" onclick="closeRestoreModal()">No</button>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
