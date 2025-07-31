<?php
session_start();

if (!isset($_SESSION["teacherid"]) || empty($_SESSION["teacherid"])) { // Check if teacherid is set and not empty
    error_log("Redirecting to teacherLogin.php: teacherid is not set or empty."); // Debugging log
    header("Location: teacherLogin.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: teacherLogin.php'); // Redirect to login page
    exit();
}



$username = "root"; 
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT t.teacherid, t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield 
            FROM teachrinf t
            WHERE t.teacherid = :teacherid";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacherid', $_SESSION["teacherid"]);
    $stmt->execute();
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        error_log("Teacher not found for teacherid: " . $_SESSION["teacherid"]); // Debugging log
        die("Error: Teacher not found. Please contact the administrator.");
    }

    // Fetch today's schedule for the teacher
    $today = date('l'); // Get the current day of the week
    $schedule = [];
    try {
        $sqlSchedule = "SELECT s.section_Name AS section_name, sch.schedule_time 
                        FROM tblschedule sch
                        INNER JOIN tblshssection s ON sch.section_ID = s.section_ID
                        WHERE sch.teacher_ID = :teacherid AND sch.schedule_date = :today";
        $stmtSchedule = $pdo->prepare($sqlSchedule);
        $stmtSchedule->bindParam(':teacherid', $_SESSION["teacherid"]);
        $stmtSchedule->bindParam(':today', $today);
        $stmtSchedule->execute();
        $schedule = $stmtSchedule->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("ERROR: Could not execute $sqlSchedule. " . $e->getMessage());
    }

    // Fetch registered sections
    $sections = [];
    try {
        $sqlSections = "SELECT sec.section_ID, sec.section_name, 
                               (SELECT COUNT(*) FROM tblshsstudent st WHERE st.section_ID = sec.section_ID) AS no_of_students
                        FROM tblshssection sec";
        $stmtSections = $pdo->query($sqlSections);
        $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("ERROR: Could not execute $sqlSections. " . $e->getMessage());
    }
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="studentPROFILE.css">
    <title>Teacher Dashboard</title>
    <style>
 * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
        }
        
        .main-header {
            border-bottom: 1px solid #ccc;
        }
        
        .img-main {
            height: 8vh;
        }
        
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: black;
        }
        
        .user-section {
            display: flex;
            align-items: center;
        }
        
        .user-role {
            font-size: 16px;
            color: #0078D4; /* Blue color for the text */
            margin-right: 15px;
        }
        
        .logout-button {
            background-color: #0055a5; /* Dark blue background */
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .logout-button:hover {
            background-color: #003f7f; /* Slightly darker blue on hover */
        }
        
        .sub-main {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50px;
            width: 100%;
        }
        
        .sub-header {
            width: 97%;
            display: flex;
            align-items: center;
            background-color: #0033cc; /* Blue background */
            padding: 10px 20px;
            color: white;
            height: 50px;
        }
        
        .menu-icon {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 20px;
            cursor: pointer;
            margin-right: 20px;
        }
        
        .menu-icon .line {
            width: 25px;
            height: 3px;
            background-color: white;
            border-radius: 2px;
        }
        
        .home-section {
            display: flex;
            align-items: center;
        }
        
        .home-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        
        .home-text {
            font-size: 22px;
            font-weight: bold;
            color: white;
        }
        
        .img-home {
            height: 30px;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
          }
          .container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          }
          .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
          }
          .profile-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 20px;
          }
          .profile-header h1 {
            font-size: 24px;
            margin: 0;
          }
          .profile-header p {
            margin: 5px 0 0;
            color: #666;
          }
          .section {
            display: flex;
            justify-content: space-between;
          }
          .section div {
            width: 48%;
          }
          .section label {
            font-size: 14px;
            color: #666;
          }
          .section input {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
          }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background-color: #f1f5f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .schedule-table th, .schedule-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .schedule-table th {
            background-color: #0047ab;
            color: white;
        }
        .schedule-table tbody {
            display: block;
            max-height: 300px; /* Adjust the height as needed */
            overflow-y: auto;
            width: 100%;
        }
        .schedule-table thead, .schedule-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .schedule-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center content horizontally */
            justify-content: space-between; /* Space out content vertically */
        }
        .card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #333;
        }
        .card p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        .card .manageButton {
            display: block;
            margin-top: 15px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            margin: 15px auto; /* Center the button horizontally */
        }
        .card .manageButton:hover {
            background-color: #0056b3;
        }
        .menu {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }
        .dropdown {
            display: none;
            position: absolute;
            top: 30px;
            right: 10px;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        .dropdown a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: black;
        }
        .dropdown a:hover {
            background-color: #f2f2f2;
        }
        /* Side Menu */
.side-menu {
    display: none; /* Initially hidden */
    position: fixed;
    top: 0;
    left: 0;
    width: 270px; /* Width of the side menu */
    height: 100%;
    background-color: #0033cc; /* Blue background */
    color: white;
    padding: 20px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    overflow-y: auto;
}

.side-menu .close-button {
    background: none;
    border: none;
    color: white;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
    display: block;
    margin-bottom: 20px;
}

.side-menu ul {
    list-style: none;
    padding: 0;
}

.side-menu ul li {
    margin: 15px 0;
}

.side-menu ul li.menu-section-title {
    font-size: 14px;
    text-transform: uppercase;
    font-weight: bold;
    margin-top: 20px;
    color: white;
    border-top: 1px solid white;
    padding-top: 10px;
}

.side-menu ul li a {
    text-decoration: none;
    display: block;
    background-color: #cce0ff; /* Light blue for buttons */
    color: #003366; /* Dark blue text */
    padding: 10px 15px;
    border-radius: 5px;
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.side-menu ul li a:hover {
    background-color: #b3ccff; /* Slightly darker on hover */
}

/* Open and Close Animations */
.side-menu.open {
    display: block;
    animation: slideIn 0.3s forwards;
}

@keyframes slideIn {
    from {
        transform: translateX(-300px);
    }
    to {
        transform: translateX(0);
    }
}
    .logout-modal {
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

    .logout-modal-content {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        width: 300px;
    }

    .logout-modal-content h3 {
        margin-bottom: 20px;
        font-size: 1.2rem;
        color: #333;
    }

    .logout-modal-content button {
        padding: 10px 20px;
        margin: 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
    }

    .logout-modal-content .confirm-logout {
        background-color: #007bff;
        color: white;
    }

    .logout-modal-content .confirm-logout:hover {
        background-color: #0056b3;
    }

    .logout-modal-content .cancel-logout {
        background-color: #f2f2f2;
        color: #333;
    }

    .logout-modal-content .cancel-logout:hover {
        background-color: #e0e0e0;
    }
    </style>

<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <h3>Are you sure you want to log out?</h3>
        <form action="" method="GET" style="display: inline;">
            <button type="submit" name="logout" value="true" class="confirm-logout">Yes</button>
        </form>
        <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
    </div>
</div>

<script>
    function showLogoutModal() {
        document.getElementById('logoutModal').style.display = 'flex';
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }
</script>
</head>
<body>
    <header class="main-header">
        <!-- Top Header -->
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Teacher</span>
                <a href="javascript:void(0);" class="logout-button" onclick="showLogoutModal()">Logout</a> <!-- Logout button -->
            </div>
        </div>
        <br>
        <!-- Sub Header -->
        <div class="sub-main">
            <div class="sub-header">
                <div class="menu-icon" id="burger-menu">
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>
                <div class="home-section">
                    <img class="img-home" src="TPC-IMAGES/teacher1-removebg-preview.png" alt="Home Icon" class="home-icon">
                    <span class="home-text">DASHBOARD</span>
                </div>
            </div>
        </div>
    </header>
    <!-- Side Menu -->
    <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="teacher_dashboard.php" class="menu-item">Dashboard</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="teacherPROFILE.php" class="menu-item">Profile</a></li>
            <li><a href="teacherSCHEDULE.php" class="menu-item">Schedule</a></li>
            <li><a href="teacherCLASSLIST.php" class="menu-item">Classlist</a></li>
            <li><a href="teacherHome.php" class="menu-item">Announcements</a></li> <!-- Added link to teacherHome.php -->
            <li class="menu-section-title">NAVIGATION</li>
            <li><a href="teacherMAP.php" class="menu-item">Campus Map</a></li>
        </ul>
    </nav>
    <div class="container">
        <h1 class="section-title">Hello! <?php echo htmlspecialchars($teacher["teachername"]); ?>!</h1>
        <h2 class="section-title">Today's Schedule</h2>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody id="scheduleContainer">
                <?php if (empty($schedule)): ?>
                    <tr>
                        <td colspan="2">No sections scheduled for today.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($schedule as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['section_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['schedule_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <h2 class="section-title">Registered Sections</h2>
        <div class="flex-container" id="sectionsContainer">
            <?php foreach ($sections as $section): ?>
                <div class="card">
                    <h3>Section: <?php echo htmlspecialchars($section['section_name']); ?></h3>
                    <p>Number of Students: <?php echo htmlspecialchars($section['no_of_students']); ?></p>
                    <p>Teacher Field: <?php echo htmlspecialchars($teacher['teacherfield'] ?? 'N/A'); ?></p> <!-- Display teacherfield -->
                    <button class="manageButton" onclick="manageAttendance('<?php echo htmlspecialchars($section['section_ID']); ?>')">Manage Attendance</button>
                    <div class="menu" onclick="toggleDropdown(this)">⋮</div>
                    <div class="dropdown">
                        <a href="view_class_list.php?section_ID=<?php echo htmlspecialchars($section['section_ID']); ?>">View Class List</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="editNoOfStudentsModal" style="display:none;">
        <h2>Edit Number of Students</h2>
        <form id="editNoOfStudentsForm">
            <input type="hidden" id="editSectionName" name="section">
            <label for="noOfStudents">Number of Students:</label><br>
            <input type="number" id="noOfStudents" name="noofstudents" min="0" required><br><br>
            <button type="button" onclick="submitEditNoOfStudents()">Save</button>
            <button type="button" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
    <script>
        const burgerMenu = document.getElementById('burger-menu');
        const sideMenu = document.getElementById('side-menu');
        const closeMenu = document.getElementById('close-menu');

        // Open the menu when clicking the burger icon
        burgerMenu.addEventListener('click', function () {
            sideMenu.classList.add('open');
        });

        // Close the menu when clicking the close button
        closeMenu.addEventListener('click', function () {
            sideMenu.classList.remove('open');
        });

        function toggleDropdown(menu) {
            const dropdown = menu.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function manageAttendance(sectionID) {
            const teacherId = <?php echo json_encode($_SESSION["teacherid"]); ?>;
            window.location.href = `insert_record.php?section=${encodeURIComponent(sectionID)}&teacherid=${encodeURIComponent(teacherId)}`;
        }

        async function fetchSchedule() {
            try {
                const response = await fetch('realtime_schedule.php');
                if (!response.ok) {
                    throw new Error('Failed to fetch schedule');
                }
                const schedule = await response.json();
                const scheduleContainer = document.getElementById('scheduleContainer');
                scheduleContainer.innerHTML = ''; // Clear existing schedule
                if (schedule.length === 0) {
                    scheduleContainer.innerHTML = '<tr><td colspan="2">No sections scheduled for today.</td></tr>';
                } else {
                    schedule.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.section_name}</td>
                            <td>${item.schedule_time}</td>
                        `;
                        scheduleContainer.appendChild(row);
                    });
                }
            } catch (error) {
                console.error('Error fetching schedule:', error);
            }
        }

        async function fetchAndUpdate() {
            try {
                const response = await fetch('realtime_teacher_data.php');
                if (!response.ok) {
                    throw new Error(`Failed to fetch teacher data: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();

                console.log('Fetched Teacher Data:', data); // Debugging: Log the fetched teacher data

                if (data.error) {
                    console.error('Backend error:', data.error); // Log backend errors
                    alert('Error: ' + data.error);
                    return;
                }

                // Update sections
                const sectionsContainer = document.getElementById('sectionsContainer');
                sectionsContainer.innerHTML = ''; // Clear existing sections

                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(function (section) {
                        const sectionCard = document.createElement('div');
                        sectionCard.className = 'card';
                        sectionCard.id = 'section-' + section.section_name;
                        sectionCard.innerHTML = `
                            <h3>Section: ${section.section_name}</h3>
                            <p>Number of Students: ${section.no_of_students}</p>
                            <p>Teacher Field: ${data.teacherfield ?? 'N/A'}</p> <!-- Added teacherfield -->
                            <button class="manageButton" onclick="manageAttendance('${section.section_ID}')">Manage Attendance</button>
                            <div class="menu" onclick="toggleDropdown(this)">⋮</div>
                            <div class="dropdown">
                                <a href="view_class_list.php?section_ID=${section.section_ID}">View Class List</a>
                            </div>
                        `;
                        sectionsContainer.appendChild(sectionCard);
                    });
                } else {
                    sectionsContainer.innerHTML = '<p>No registered sections found.</p>';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('An error occurred while fetching the data.');
            }
        }

        async function submitEditNoOfStudents() {
            const form = document.getElementById('editNoOfStudentsForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('update_noofstudents.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const result = await response.json();
                    alert(result.success || 'Number of students updated successfully!');
                    closeEditModal();
                    fetchAndUpdate();
                } else {
                    const error = await response.json();
                    alert(error.error || 'Failed to update number of students.');
                }
            } catch (error) {
                console.error('Error updating number of students:', error);
                alert('An error occurred while updating the number of students.');
            }
        }

        function closeEditModal() {
            document.getElementById('editNoOfStudentsModal').style.display = 'none';
        }

        // Logout confirmation
   

        fetchSchedule(); // Initial fetch for schedule
        setInterval(fetchSchedule, 1500); // Fetch schedule every 1.5 seconds
        fetchAndUpdate(); // Initial fetch for other data
        setInterval(fetchAndUpdate, 1500); // Fetch other data every 1.5 seconds
    </script>
</body>
</html>
