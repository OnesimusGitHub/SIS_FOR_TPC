<?php
$username = "root"; 
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

$absentRecords = [];
$sections = [];
$failingStudents = 0;
$failingStudentsList = [];
$monthlyAbsences = 0;
$weeklyAbsences = 0;
$todayAbsences = 0;

try {
    $sql = "SELECT Name, Section, absent, date, teacher_ID FROM sis.student WHERE absent > 0 ORDER BY absent DESC, date ASC";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        $studentAbsences = [];
        $today = date("Y-m-d");
        $weekAgo = date("Y-m-d", strtotime("-7 days"));
        $monthAgo = date("Y-m-d", strtotime("-1 month"));

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $section = $row["Section"];
            $monthYear = date("F Y", strtotime($row["date"]));
            if (!isset($absentRecords[$section])) {
                $absentRecords[$section] = [];
            }
            if (!isset($absentRecords[$section][$monthYear])) {
                $absentRecords[$section][$monthYear] = [];
            }
            $absentRecords[$section][$monthYear][] = $row;
            if (!in_array($section, $sections)) {
                $sections[] = $section;
            }

            $studentKey = $row["Name"] . "-" . $section . "-" . (int)$row["teacher_ID"]; // Ensure teacher_ID is treated as an integer
            if (!isset($studentAbsences[$studentKey])) {
                $studentAbsences[$studentKey] = 0;
            }
            $studentAbsences[$studentKey] += $row["absent"];

            // Calculate absences for today, this week, and this month
            if ($row["date"] === $today) {
                $todayAbsences += $row["absent"];
            }
            if ($row["date"] >= $weekAgo) {
                $weeklyAbsences += $row["absent"];
            }
            if ($row["date"] >= $monthAgo) {
                $monthlyAbsences += $row["absent"];
            }
        }
        unset($result);

        // Sort sections alphabetically
        sort($sections);

        foreach ($studentAbsences as $studentKey => $absences) {
            if ($absences >= 3) {
                $failingStudents++;
                list($name, $section, $teacherID) = explode("-", $studentKey);
                $failingStudentsList[] = [
                    "name" => $name,
                    "section" => $section,
                    "teacher_ID" => (int)$teacherID, // Ensure teacher_ID is treated as an integer
                    "absences" => $absences
                ];
            }
        }
    } else {
        echo "No records found.";
    }
} catch(PDOException $e) {
    die("ERROR: Could not execute $sql. " . $e->getMessage());
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absent Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        header.main-header {
            background: linear-gradient(to right, #0630C2, #007bff);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        header .logo-section img {
            height: 50px;
        }

        header .user-section {
            display: flex;
            align-items: center;
        }

        header .user-role {
            margin-right: 15px;
            font-weight: bold;
        }

        header .logout-button {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        header .logout-button:hover {
            background-color: #cc0000;
        }

        .container {
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #0630C2;
            margin-bottom: 20px;
        }

        .searchBarContainer {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .searchBar {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 50%;
            box-sizing: border-box;
        }

        .layout-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .failingStudentsBox {
            flex: 1 1 30%;
            padding: 15px;
            background-color: #ffe6e6;
            border: 1px solid #ff4d4d;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .failingStudentsBox h3 {
            color: #cc0000;
        }

        .sectionDropdown {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 200px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sectionDropdown:hover {
            background-color: #0056b3;
        }

        .datePicker {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .datePicker input {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .datePicker button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .datePicker button:hover {
            background-color: #0056b3;
        }

        .recordsContainer {
            margin-top: 20px;
        }

        .month-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e6f7ff;
            border: 1px solid #007bff;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .month-section h3 {
            color: #333;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }

        td {
            text-align: left;
            padding: 8px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 60px;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-content h2 {
            color: #0630C2;
        }

        .modal-content table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-content table, .modal-content th, .modal-content td {
            border: 1px solid #ccc;
        }

        .modal-content th {
            background-color: #f2f2f2;
        }

        .modal-content td {
            padding: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .back-button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .back-button:active {
            transform: scale(1);
        }
    </style>
</head>
<body>
<header class="main-header">
    <div class="logo-section">
        <img src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo">
    </div>
    <div class="user-section">
        <span class="user-role">Admin</span>
        <button class="logout-button" onclick="window.location.href='shonget.php'">Logout</button>
    </div>
</header>

<div class="container">
    <!-- Enhanced back button -->
    <a href="shonget.php" class="back-button" style="margin-bottom: 20px; display: inline-block;">Back to Dashboard</a>
    <h2>Absent Records Categorized by Month and Section</h2>
    <div class="searchBarContainer">
        <input type="text" id="searchInput" class="searchBar" onkeyup="filterRecords()" placeholder="Search for student names...">
    </div>
    <div class="layout-container">
        <div class="failingStudentsBox">
            <h3>Failing Students</h3>
            <p>Number of failing students: <span id="failingStudentsCount"><?php echo $failingStudents; ?></span></p>
            <button onclick="document.getElementById('failingStudentsModal').style.display='block'">View Failing Students</button>
        </div>
        <div>
            <select id="sectionDropdown" onchange="filterBySection(this.value)" class="sectionDropdown">
                <option value="all">All Sections</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="datePicker">
        <label for="fromDate">From:</label>
        <input type="date" id="fromDate">
        <label for="toDate">To:</label>
        <input type="date" id="toDate">
        <button onclick="filterByDate()">Apply</button>
    </div>
    <div id="recordsContainer" class="recordsContainer">
        <?php if (!empty($absentRecords)): ?>
            <?php foreach ($absentRecords as $section => $months): ?>
                <div class="month-section" data-section="<?php echo htmlspecialchars($section); ?>">
                    <h2>Section: <?php echo htmlspecialchars($section); ?></h2>
                    <?php foreach ($months as $monthYear => $records): ?>
                        <h3>Month: <?php echo htmlspecialchars($monthYear); ?></h3>
                        <table>
                            <tr>
                                <th>Name</th>
                                <th>Section</th>
                                <th>Absent</th>
                                <th>Date</th>
                                <th>Teacher ID</th>
                            </tr>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record["Name"]); ?></td>
                                    <td><?php echo htmlspecialchars($record["Section"]); ?></td>
                                    <td><?php echo htmlspecialchars($record["absent"]); ?></td>
                                    <td><?php echo htmlspecialchars($record["date"]); ?></td>
                                    <td><?php echo htmlspecialchars((int)$record["teacher_ID"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No absent records found.</p>
        <?php endif; ?>
    </div>
</div>

<div id="failingStudentsModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('failingStudentsModal').style.display='none'">&times;</span>
        <h2>Failing Students</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Section</th>
                <th>Teacher ID</th>
                <th>Absences</th>
            </tr>
            <?php foreach ($failingStudentsList as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student["name"]); ?></td>
                    <td><?php echo htmlspecialchars($student["section"]); ?></td>
                    <td><?php echo htmlspecialchars($student["teacher_ID"]); ?></td>
                    <td><?php echo htmlspecialchars($student["absences"]); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
    function filterBySection(section) {
        const sections = document.querySelectorAll('.month-section');
        sections.forEach(sec => {
            sec.style.display = (sec.getAttribute('data-section') === section || section === 'all') ? '' : 'none';
        });
    }

    function filterRecords() {
        const input = document.getElementById('searchInput').value.toUpperCase();
        const sections = document.querySelectorAll('.month-section');
        sections.forEach(section => {
            const tables = section.querySelectorAll('table');
            let hasVisibleRow = false;
            tables.forEach(table => {
                const rows = table.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    if (index === 0) return; // Skip header row
                    const cells = row.querySelectorAll('td');
                    const match = Array.from(cells).some(cell => cell.textContent.toUpperCase().includes(input));
                    row.style.display = match ? '' : 'none';
                    if (match) hasVisibleRow = true;
                });
            });
            section.style.display = hasVisibleRow ? '' : 'none';
        });
    }

    function filterByDate() {
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        const sections = document.querySelectorAll('.month-section');
        sections.forEach(section => {
            const tables = section.querySelectorAll('table');
            let hasVisibleRow = false;
            tables.forEach(table => {
                const rows = table.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    if (index === 0) return; // Skip header row
                    const date = row.querySelectorAll('td')[3].textContent;
                    const inRange = (!fromDate || date >= fromDate) && (!toDate || date <= toDate);
                    row.style.display = inRange ? '' : 'none';
                    if (inRange) hasVisibleRow = true;
                });
            });
            section.style.display = hasVisibleRow ? '' : 'none';
        });
    }
</script>
</body>
</html>
