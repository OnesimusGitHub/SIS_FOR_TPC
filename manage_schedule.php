<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    // Prevent redirection loop by ensuring the current page is not adminLogin.php
    if (basename($_SERVER['PHP_SELF']) !== 'adminLogin.php') {
        header("Location: adminLogin.php"); // Redirect to admin login page
        exit;
    }
}
$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

$teacherid = $_GET['teacherid'] ?? null;

if (!$teacherid) {
    die("Invalid teacher ID.");
}

// Fetch teacher's field
$teacherField = '';
try {
    $sql = "SELECT teacherfield FROM teachrinf WHERE teacherid = :teacherid";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacherid', $teacherid);
    $stmt->execute();
    $teacherField = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("ERROR: Could not fetch teacher's field. " . $e->getMessage());
}

// Fetch teacher's full name
$teacherName = '';
try {
    $sql = "SELECT CONCAT(teachername, ' ', teachermidd, ' ', teacherlastname) AS fullname FROM teachrinf WHERE teacherid = :teacherid";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacherid', $teacherid);
    $stmt->execute();
    $teacherName = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("ERROR: Could not fetch teacher's name. " . $e->getMessage());
}

// Fetch sections assigned to the teacher
$sections = [];
try {
    $sql = "SELECT s.section_ID, s.section_Name 
            FROM tblshssection s
            INNER JOIN tblsecteacher st ON s.section_ID = st.section_ID
            WHERE st.teacher_ID = :teacherid";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacherid', $teacherid);
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not fetch sections. " . $e->getMessage());
}

// Handle schedule updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_schedule_id'])) {
    $section_ID = $_POST['section_ID'] ?? null;
    $schedule_date = $_POST['schedule_date'] ?? null;
    $schedule_room = $_POST['schedule_room'] ?? null;
    $schedule_time = $_POST['schedule_time'] ?? null;

    if ($section_ID && $schedule_date && $schedule_room && $schedule_time) {
        try {
            // Check if the schedule time already exists for the same day and section
            $sql = "SELECT COUNT(*) FROM tblschedule 
                    WHERE section_ID = :section_ID AND schedule_date = :schedule_date AND schedule_time = :schedule_time";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':section_ID', $section_ID);
            $stmt->bindParam(':schedule_date', $schedule_date);
            $stmt->bindParam(':schedule_time', $schedule_time);
            $stmt->execute();
            $existingCount = $stmt->fetchColumn();

            if ($existingCount > 0) {
                $error = "A schedule already exists for this section, day, and time.";
            } else {
                // Insert or update the schedule
                $sql = "INSERT INTO tblschedule (section_ID, teacher_ID, schedule_date, schedule_room, schedule_time)
                        VALUES (:section_ID, :teacherid, :schedule_date, :schedule_room, :schedule_time)
                        ON DUPLICATE KEY UPDATE
                        schedule_date = :schedule_date, schedule_room = :schedule_room, schedule_time = :schedule_time";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':section_ID', $section_ID);
                $stmt->bindParam(':teacherid', $teacherid);
                $stmt->bindParam(':schedule_date', $schedule_date);
                $stmt->bindParam(':schedule_room', $schedule_room);
                $stmt->bindParam(':schedule_time', $schedule_time);
                $stmt->execute();
                $success = "Schedule updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "ERROR: Could not update schedule. " . $e->getMessage();
        }
    } else {
        $error = "All fields are required.";
    }
}

// Handle schedule deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_schedule_id'])) {
    $schedule_id = $_POST['delete_schedule_id'];

    try {
        $sql = "DELETE FROM tblschedule WHERE schedule_ID = :schedule_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':schedule_id', $schedule_id);
        $stmt->execute();
        $success = "Schedule deleted successfully!";
    } catch (PDOException $e) {
        $error = "ERROR: Could not delete schedule. " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        header {
            background-color: #0047ab;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            background-color: #0047ab;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #003580;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f9;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .save-modal {
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

        .save-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        .save-modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .save-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .save-modal-content .confirm-save {
            background-color: #007bff;
            color: white;
        }

        .save-modal-content .confirm-save:hover {
            background-color: #0056b3;
        }

        .save-modal-content .cancel-save {
            background-color: #f2f2f2;
            color: #333;
        }

        .save-modal-content .cancel-save:hover {
            background-color: #e0e0e0;
        }

        /* Delete Modal Styles */
        .delete-modal {
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

        .delete-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        .delete-modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .delete-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .delete-modal-content .confirm-delete {
            background-color: #dc3545;
            color: white;
        }

        .delete-modal-content .confirm-delete:hover {
            background-color: #c82333;
        }

        .delete-modal-content .cancel-delete {
            background-color: #f2f2f2;
            color: #333;
        }

        .delete-modal-content .cancel-delete:hover {
            background-color: #e0e0e0;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
            text-align: center;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        .scrollable-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;
        }

        .scrollable-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .scrollable-container th, .scrollable-container td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .scrollable-container th {
            background-color: #f4f4f9;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .scrollable-container tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .scrollable-container tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="color: white;">Manage Schedule</h1>
            <a href="admin_dashboard.php" class="back-button">Back to Dashboard</a> <!-- Back button -->
        </div>
    </header>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <h2>Teacher: <?php echo htmlspecialchars($teacherName); ?></h2>
            <h3>Field: <?php echo htmlspecialchars($teacherField); ?></h3>
        </div>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

        <form method="POST" onsubmit="showSaveModal(event)">
            <label for="section_ID">Section:</label>
            <select id="section_ID" name="section_ID" required>
                <option value="">Select Section</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?php echo htmlspecialchars($section['section_ID']); ?>"><?php echo htmlspecialchars($section['section_Name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="schedule_date">Day:</label>
            <select id="schedule_date" name="schedule_date" required>
                <option value="">Select Day</option>
                <option value="MONDAY">MONDAY</option>
                <option value="TUESDAY">TUESDAY</option>
                <option value="WEDNESDAY">WEDNESDAY</option>
                <option value="THURSDAY">THURSDAY</option>
                <option value="FRIDAY">FRIDAY</option>
                <option value="SATURDAY">SATURDAY</option>
            </select>

            <label for="schedule_room">Room:</label>
            <input type="text" id="schedule_room" name="schedule_room" placeholder="Enter room" required>

            <label for="schedule_time">Time:</label>
            <select id="schedule_time" name="schedule_time" required>
                <option value="">Select Time</option>
                <option value="10:15am-12:15pm">10:15am-12:15pm</option>
                <option value="1:00pm-3:00pm">1:00pm-3:00pm</option>
                <option value="3:00pm-5:00pm">3:00pm-5:00pm</option>
                <option value="9:00am-10:00am">9:00am-10:00am</option>
                <option value="10:15am-12:15pm">10:15am-12:15pm</option>
                <option value="1:00pm-3:00pm">1:00pm-3:00pm</option>
                <option value="8:00am-5:00pm">8:00am-5:00pm</option>
                <option value="11:15am-12:15am">11:15am-12:15am</option>
            </select>

            <button type="submit">Save Schedule</button>
        </form>

        <h2>Existing Schedules</h2>
        <div class="scrollable-container">
            <table>
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Day</th>
                        <th>Room</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $sql = "SELECT sch.schedule_ID, s.section_Name, sch.schedule_date, sch.schedule_room, sch.schedule_time
                                FROM tblschedule sch
                                JOIN tblshssection s ON sch.section_ID = s.section_ID
                                WHERE sch.teacher_ID = :teacherid";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':teacherid', $teacherid);
                        $stmt->execute();
                        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($schedules as $schedule) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($schedule['section_Name']) . "</td>";
                            echo "<td>" . htmlspecialchars($schedule['schedule_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($schedule['schedule_room']) . "</td>";
                            echo "<td>" . htmlspecialchars($schedule['schedule_time']) . "</td>";
                            echo "<td><button class='delete-button' onclick='showDeleteModal(" . htmlspecialchars($schedule['schedule_ID']) . ")'>Delete</button></td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='5'>ERROR: Could not fetch schedules. " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="saveModal" class="save-modal">
        <div class="save-modal-content">
            <h3>Are you sure you want to save this schedule?</h3>
            <button id="confirmSaveButton" class="confirm-save">Yes</button>
            <button class="cancel-save" onclick="closeSaveModal()">No</button>
        </div>
    </div>

    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <h3>Are you sure you want to delete this schedule?</h3>
            <form method="POST" id="deleteScheduleForm">
                <input type="hidden" name="delete_schedule_id" id="deleteScheduleId">
                <button type="submit" class="confirm-delete">Yes</button>
                <button type="button" class="cancel-delete" onclick="closeDeleteModal()">No</button>
            </form>
        </div>
    </div>

    <script>
        function showSaveModal(event) {
            event.preventDefault(); // Prevent form submission
            document.getElementById('saveModal').style.display = 'flex';
        }

        function closeSaveModal() {
            document.getElementById('saveModal').style.display = 'none';
        }

        document.getElementById('confirmSaveButton').addEventListener('click', () => {
            document.querySelector('form').submit(); // Submit the form after confirmation
        });

        function showDeleteModal(scheduleId) {
            document.getElementById('deleteScheduleId').value = scheduleId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
    </script>
</body>
</html>
