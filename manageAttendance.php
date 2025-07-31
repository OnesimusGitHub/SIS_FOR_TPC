<?php
// Retrieve the section from the query string
$section = isset($_GET['section']) ? $_GET['section'] : 'Unknown Section';

// Example: Fetch students for the selected section from the database
$students = [
    ["id" => "1001", "name" => "John Doe", "status" => "Present"],
    ["id" => "1002", "name" => "Jane Smith", "status" => "Absent"],
    ["id" => "1003", "name" => "Alice Brown", "status" => "Present"]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance - <?php echo htmlspecialchars($section); ?></title>
    <link rel="stylesheet" href="teacherSCHEDULE.css">
    <style>
        /* General Styling */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F8F9FA;
            color: #343A40;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .breadcrumb {
            font-size: 0.9em;
            color: #6C757D;
        }

        .breadcrumb a {
            color: #007BFF;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Form Section */
        .form-card {
            padding: 20px;
            background: #F8F9FA;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .attendance-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .form-group {
            flex: 1 1 calc(33.333% - 15px);
            min-width: 200px;
        }

        label {
            font-size: 0.9em;
            margin-bottom: 5px;
            color: #6C757D;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        /* Table Section */
        .table-container {
            margin-top: 20px;
        }

        .table-header {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .search-bar {
            width: 250px;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .schedule-table th, .schedule-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .schedule-table th {
            background-color: #007BFF;
            color: #fff;
        }

        .schedule-table tr:nth-child(even) {
            background-color: #F8F9FA;
        }

        .schedule-table tr:hover {
            background-color: #E9ECEF;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>Manage Attendance</h1>
            <nav class="breadcrumb">
                <a href="teacherSCHEDULE.php">Attendance</a> > Manage Attendance
            </nav>
        </header>

        <!-- Form Section -->
        <div class="form-card">
            <h2>Attendance Form</h2>
            <form class="attendance-form">
                <div class="form-group">
                    <label for="student-id">Student ID:</label>
                    <select id="student-id" name="student-id" onchange="populateStudentName()">
                        <option value="">Select a student...</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo htmlspecialchars($student['id']); ?>" data-name="<?php echo htmlspecialchars($student['name']); ?>">
                                <?php echo htmlspecialchars($student['id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="student-name">Name:</label>
                    <input type="text" id="student-name" name="student-name" placeholder="Enter student name" readonly>
                </div>
                <div class="form-group">
                    <label for="attendance-date">Date:</label>
                    <input type="date" id="attendance-date" name="attendance-date">
                </div>
                <button type="button" class="btn btn-primary">Insert Record</button>
            </form>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-header">
                <input type="text" class="search-bar" placeholder="Search by student name or ID">
            </div>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['id']); ?></td>
                            <td>
                                <!-- Actions can be added here -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function saveAttendance() {
            alert('Attendance saved successfully!');
            // Add AJAX or form submission logic here
        }

        function populateStudentName() {
            const studentSelect = document.getElementById('student-id');
            const studentNameInput = document.getElementById('student-name');
            const selectedOption = studentSelect.options[studentSelect.selectedIndex];
            const studentName = selectedOption.getAttribute('data-name');
            studentNameInput.value = studentName || '';
        }
    </script>
</body>
</html>