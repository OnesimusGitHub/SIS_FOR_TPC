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

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: adminLogin.php");
    exit;
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

// Automatically set `studstat` to `ARCHIVED` for students whose `school_end` has passed
$currentDate = date('Y-m-d');
$conn->query("
    UPDATE tblshsstudent 
    SET studstat = 'ARCHIVED' 
    WHERE school_end < '$currentDate' AND (studstat IS NULL OR studstat != 'ARCHIVED')
");

// Fetch strands
$strands = [];
$strandResult = $conn->query("SELECT strand_ID, strand_code FROM tblstrand");
if ($strandResult->num_rows > 0) {
    while ($row = $strandResult->fetch_assoc()) {
        $strands[] = $row;
    }
}

// Handle search request
$searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Fetch all students with search functionality
$allStudents = [];
$allStudentsQuery = "
    SELECT shsstud_ID, shstud_firstname, shstud_lastname, shstud_pfp, strand_ID, section_ID, student_grade, school_end 
    FROM tblshsstudent 
    WHERE (studstat IS NULL OR studstat != 'ARCHIVED')
      AND (shstud_firstname LIKE '%$searchQuery%' OR shstud_lastname LIKE '%$searchQuery%')
";
$studentResult = $conn->query($allStudentsQuery);
if ($studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $allStudents[] = $row;
    }
}

// Fetch students without strand
$studentsWithoutStrand = [];
$studentsWithoutStrandQuery = "
    SELECT shsstud_ID, shstud_firstname, shstud_lastname, shstud_pfp, strand_ID, section_ID, student_grade 
    FROM tblshsstudent 
    WHERE (strand_ID IS NULL OR strand_ID = '') 
      AND (studstat IS NULL OR studstat != 'ARCHIVED')
";
$studentResult = $conn->query($studentsWithoutStrandQuery);
if ($studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $studentsWithoutStrand[] = $row;
    }
}

// Fetch students without section
$studentsWithoutSection = [];
$studentsWithoutSectionQuery = "
    SELECT shsstud_ID, shstud_firstname, shstud_lastname, shstud_pfp, strand_ID, section_ID, student_grade 
    FROM tblshsstudent 
    WHERE (section_ID IS NULL OR section_ID = '') 
      AND (studstat IS NULL OR studstat != 'ARCHIVED')
";
$studentResult = $conn->query($studentsWithoutSectionQuery);
if ($studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $studentsWithoutSection[] = $row;
    }
}

// Fetch sections
$sections = [];
$sectionResult = $conn->query("SELECT section_ID, section_Name, shsgrade, strand_ID FROM tblshssection");
if ($sectionResult->num_rows > 0) {
    while ($row = $sectionResult->fetch_assoc()) {
        $sections[] = $row;
    }
}



$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="manageStudent.css" rel="stylesheet"> <!-- Ensure this path is correct -->
    <title>Manage Student</title>
    <link rel="stylesheet" href="studentPROFILE.css">
    <style>
        .flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card {
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            width: 200px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
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
        }
        .card p {
            margin: 5px 0;
            font-size: 0.9rem;
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

        /* Unique styles for Students Without Strand */
        .card.strand-card {
            background-color: #fff4e6;
            border-color: #ffa726;
        }

        /* Unique styles for Students Without Section */
        .card.section-card {
            background-color: #e6f7ff;
            border-color: #42a5f5;
        }

        /* Unique styles for All Students */
        .card.all-students-card {
            background-color: #f9f9f9;
            border-color: #ccc;
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
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            width: 450px;
            max-width: 90%;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-content h3, .modal-content h2 {
            text-align: center;
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
        }

        .modal-content label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        .modal-content select,
        .modal-content input[type="date"],
        .modal-content button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .modal-content button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .modal-content button:hover {
            background-color: #0056b3;
        }

        .modal-content .cancel-button {
            background-color: #f2f2f2;
            color: #333;
        }

        .modal-content .cancel-button:hover {
            background-color: #e0e0e0;
        }

        .close-button {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-button:hover {
            color: red;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

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
            margin: 0;
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

        #allStudents {
            max-height: 500px; /* Set a maximum height for the container */
            overflow-y: auto; /* Enable vertical scrolling */
            padding: 10px; /* Add padding inside the container */
            background-color: #f9f9f9; /* Light background color */
            border: 1px solid #ddd; /* Border color */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add box shadow */
        }

        #allStudents::-webkit-scrollbar {
            width: 8px;
        }

        #allStudents::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 4px;
        }

        #allStudents::-webkit-scrollbar-thumb:hover {
            background-color: #aaa;
        }

        .form-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1001;
        }

        .blurred-background {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideDown 0.3s ease-in-out;
            border: 2px solid #007bff;
        }

        .modal-content h3 {
            text-align: center;
            font-size: 1.5rem;
            color: #007bff;
            margin-bottom: 15px;
        }

        .modal-content label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .modal-content select,
        .modal-content input[type="date"],
        .modal-content button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .modal-content button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .modal-content button:hover {
            background-color: #0056b3;
        }

        .modal-content .cancel-button {
            background-color: #f2f2f2;
            color: #333;
        }

        .modal-content .cancel-button:hover {
            background-color: #e0e0e0;
        }

        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-button:hover {
            color: red;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        .archive-modal {
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

        .archive-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        .archive-modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .archive-modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .archive-modal-content .confirm-archive {
            background-color:rgb(49, 126, 30);
            color: white;
        }

        .archive-modal-content .confirm-archive:hover {
            background-color:rgb(28, 90, 20);
        }

        .archive-modal-content .cancel-archive {
            background-color:  #dc3545;
            color: white;
        }

        .archive-modal-content .cancel-archive:hover {
            background-color: #c82333;
        }

        .students-section {
            max-height: 300px; /* Set a maximum height for the section */
            overflow-y: auto; /* Enable vertical scrolling */
            padding: 10px; /* Add padding inside the section */
            background-color: #f9f9f9; /* Light background color */
            border: 1px solid #ddd; /* Border color */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add box shadow */
        }

        .students-section::-webkit-scrollbar {
            width: 8px; /* Set the width of the scrollbar */
        }

        .students-section::-webkit-scrollbar-thumb {
            background-color: #ccc; /* Set the color of the scrollbar thumb */
            border-radius: 4px; /* Round the corners of the scrollbar thumb */
        }

        .students-section::-webkit-scrollbar-thumb:hover {
            background-color: #aaa; /* Change the color on hover */
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Admin</span>
                <button class="logout-button" onclick="showLogoutModal()">Logout</button>
            </div>
        </div>
        <br>
        <div class="sub-main">
            <div class="sub-header">
                <div class="menu-icon" id="burger-menu">
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>
                <div class="home-section">
                    <img class="img-home" src="TPC-IMAGES/student.png" alt="Home Icon" class="home-icon">
                    <span class="home-text">Manage Students</span>
                </div>
            </div>
        </div>

        <nav class="side-menu" id="side-menu">
            <button class="close-button" id="close-menu">&times;</button>
            <ul>
            <li class="menu-section-title">MY</li>
            <li><a href="admin_dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="adminPROFILE.php" class="menu-item">Profile</a></li>
            <li class="menu-section-title">ADD ACCOUNTS</li>
            <li><a href="admin_student_addSHS.php" class="menu-item">Add SHS Student</a></li>
            <li><a href="admin_instructor_add.php" class="menu-item">Add Instructor</a></li>
            <li><a href="admin_addAdmin.php" class="menu-item">Add Admin</a></li>
            <li><a href="admin_cashier_add.php" class="menu-item">Add Cashier</a></li>
            <li class="menu-section-title">ACCOUNTS</li>
            <li><a href="manageStudent.php" class="menu-item">Manage Students</a></li>
            <li><a href="adminAccounts.php" class="menu-item">Manage Admins</a></li>
            <li><a href="registrarAccounts.php" class="menu-item">Manage Cashiers</a></li>
            <li class="menu-section-title">ANNOUNCEMENTS</li>
            <li><a href="adminHome.php" class="menu-item">Announcements</a></li>
            <li class="menu-section-title">ANALYTICS</li>
            <li><a href="shonget.php" class="menu-item">Analytics</a></li>
            </ul>
        </nav>
    </header>
   
    <div class="container" style="margin-left: 150px;">
        <div style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.location.href='display_students_by_school_end.php'" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                View Students by School End Date
            </button>
        </div>
        <!-- Add a search form -->
        <form method="GET" action="manageStudent.php" style="margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($searchQuery); ?>" style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;">
            <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Search</button>
        </form>
        <div class="students-container">
    <!-- Students Without Strand -->
    <div class="students-section">
        <h2>Students Without Strand</h2>
        <ul id="studentsWithoutStrand">
            <?php foreach ($studentsWithoutStrand as $student): ?>
                <li>
                    <?php echo htmlspecialchars($student['shstud_firstname'] . " " . $student['shstud_lastname']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Students Without Section -->
    <div class="students-section">
        <h2>Students Without Section</h2>
        <ul id="studentsWithoutSection">
            <?php foreach ($studentsWithoutSection as $student): ?>
                <li>
                    <?php echo htmlspecialchars($student['shstud_firstname'] . " " . $student['shstud_lastname']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Separate All Students Section -->
<div class="all-students-container" style="width: 890px; margin-top: -580px; margin-left: 800px; position: absolute; height: 598px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
    <h2>All Students</h2>
    <div class="flex-container" id="allStudents">
        <?php foreach ($allStudents as $student): ?>
            <div class="card all-students-card" id="student-<?php echo htmlspecialchars($student['shsstud_ID']); ?>">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($student['shstud_pfp']); ?>" alt="Student Picture">
                <h3><?php echo htmlspecialchars($student['shstud_firstname'] . " " . $student['shstud_lastname']); ?></h3>
                <p>Grade: <?php echo htmlspecialchars($student['student_grade'] ?? 'Not Assigned'); ?></p>
                <p>Strand: <?php echo htmlspecialchars($student['strand_ID'] ?? 'Not Assigned'); ?></p>
                <p>Section: <?php echo htmlspecialchars($student['section_ID'] ?? 'Not Assigned'); ?></p>
                <p>School Year: <span id="school-year-<?php echo htmlspecialchars($student['shsstud_ID']); ?>">Loading...</span></p>
                <p>School End: <span id="school-end-<?php echo htmlspecialchars($student['shsstud_ID']); ?>">
                    <?php echo htmlspecialchars($student['school_end'] ?? 'Not Assigned'); ?>
                </span></p>
                <div class="menu" onclick="toggleDropdown(this)">⋮</div>
                <div class="dropdown">
                    <a href="#" onclick="showAssignGradeForm('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">Assign Grade</a>
                    <a href="#" onclick="showAssignStrandForm('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">Assign Strand</a>
                    <a href="#" onclick="showAssignSectionForm('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">Assign Section</a>
                    <a href="admin_student_informationSHS.php?studentid=<?php echo htmlspecialchars($student['shsstud_ID']); ?>">View Student Information</a>
                    <a href="#" onclick="showArchiveModal('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">Archive Student</a>
                    <a href="#" onclick="showAssignSchoolYearForm('<?php echo htmlspecialchars($student['shsstud_ID']); ?>')">Assign School Year</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
    </div>

<!-- Modal for Assign Forms -->
<div id="assignFormsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-button" onclick="closeAssignFormsModal()">&times;</span>
        <form id="assignGradeForm" style="display: none;">
            <label for="grade">Select Grade:</label>
            <select id="grade" name="grade" required>
                <option value="">-- Select Grade --</option>
                <option value="GRADE 11">GRADE 11</option>
                <option value="GRADE 12">GRADE 12</option>
            </select>
            <button type="button" onclick="assignGrade()">Confirm</button>
        </form>

        <form id="assignStrandForm" style="display: none;">
            <label for="strand">Select Strand:</label>
            <select id="strand" name="strand" required>
                <option value="">-- Select Strand --</option>
                <?php foreach ($strands as $strand): ?>
                    <option value="<?php echo $strand['strand_ID']; ?>">
                        <?php echo htmlspecialchars($strand['strand_code']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="assignStrand()">Confirm</button>
        </form>

        <form id="assignSectionForm" style="display: none;">
            <label for="section">Select Section:</label>
            <select id="section" name="section" required>
                <option value="">-- Select Section --</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?php echo $section['section_ID']; ?>">
                        <?php echo htmlspecialchars($section['section_Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="assignSection()">Confirm</button>
        </form>

        <form id="assignSubjectForm" style="display: none;">
            <label for="subject">Select Subject:</label>
            <select id="subject" name="subject" required>
                <option value="">-- Select Subject --</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?php echo $subject['subject_ID']; ?>">
                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="assignSubject()">Confirm</button>
        </form>
    </div>
</div>

<!-- Modal for Assign School Year -->
<div id="assignSchoolYearModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-button" onclick="closeAssignSchoolYearModal()">&times;</span>
        <form id="assignSchoolYearForm">
            <label for="school_year">Start of School Year:</label>
            <input type="date" id="school_year" name="school_year" required>
            <label for="school_end">End of School Year:</label>
            <input type="date" id="school_end" name="school_end" required>
            <button type="button" onclick="assignSchoolYear()">Confirm</button>
        </form>
    </div>
</div>

<div id="assignGradeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-button" onclick="closeAssignGradeModal()">&times;</span>
        <h3>Assign Grade to Student</h3>
        <form id="assignGradeForm">
            <label for="grade">Grade:</label>
            <select id="grade" name="grade" required>
                <option value="">-- Select Grade --</option>
                <option value="GRADE 11">GRADE 11</option>
                <option value="GRADE 12">GRADE 12</option>
            </select>
            <button type="button" onclick="assignGrade()">Assign Grade</button>
            <button type="button" class="cancel-button" onclick="closeAssignGradeModal()">Cancel</button>
        </form>
    </div>
</div>

<div id="assignStrandModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-button" onclick="closeAssignStrandModal()">&times;</span>
        <h3>Assign Strand to Student</h3>
        <form id="assignStrandForm">
            <label for="strand">Strand:</label>
            <select id="strand" name="strand" required>
                <option value="">-- Select Strand --</option>
                <?php foreach ($strands as $strand): ?>
                    <option value="<?php echo $strand['strand_ID']; ?>">
                        <?php echo htmlspecialchars($strand['strand_code']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="assignStrand()">Assign Strand</button>
            <button type="button" class="cancel-button" onclick="closeAssignStrandModal()">Cancel</button>
        </form>
    </div>
</div>

<div id="assignSectionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Assign Section to Student</h3>
        <form id="assignSectionForm" onsubmit="return handleAssignSection(event)">
            <label for="section">Section:</label>
            <select id="section" name="section" required>
                <option value="">-- Select Section --</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?php echo $section['section_ID']; ?>">
                        <?php echo htmlspecialchars($section['section_Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Assign Section</button>
        </form>
    </div>
</div>

<div id="assignSchoolYearModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Assign School Year to Student</h3>
        <form id="assignSchoolYearForm">
            <label for="school_year">Start of School Year:</label>
            <input type="date" id="school_year" name="school_year" required>
            <label for="school_end">End of School Year:</label>
            <input type="date" id="school_end" name="school_end" required>
            <button type="button" onclick="assignSchoolYear()" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Assign School Year</button>
            <button type="button" onclick="closeAssignSchoolYearModal()" style="background-color: #f2f2f2; color: black; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cancel</button>
        </form>
    </div>
</div>

<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <h3>Are you sure you want to log out?</h3>
        <form action="" method="GET" style="display: inline;">
            <button type="submit" name="logout" value="true" class="confirm-logout">Yes</button>
        </form>
        <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
    </div>
</div>

<div id="archiveModal" class="archive-modal">
    <div class="archive-modal-content">
        <h3>Are you sure you want to archive this student?</h3>
        <button id="confirmArchiveButton" class="confirm-archive">Yes</button>
        <button class="cancel-archive" onclick="closeArchiveModal()">No</button>
    </div>
</div>

    <script>
        // Logout function
        function logout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "manageStudent.php?logout=true";
            }
        }

        let currentStudentId = null;

        function toggleDropdown(menu) {
            const dropdown = menu.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function showAssignGradeForm(studentId) {
            openAssignFormsModal('assignGradeForm', studentId);
        }

        function showAssignStrandForm(studentId) {
            openAssignFormsModal('assignStrandForm', studentId);
        }

        async function showAssignSectionForm(studentId) {
            openAssignFormsModal('assignSectionForm', studentId);

            // Autofill sections based on the student's grade and strand
            const studentCard = document.getElementById(`student-${studentId}`);
            if (!studentCard) {
                console.error(`Student card with ID student-${studentId} not found.`);
                alert('Error: Student card not found. Please refresh the page and try again.');
                return;
            }

            const gradeElement = studentCard.querySelector('p:nth-child(3)');
            const strandElement = studentCard.querySelector('p:nth-child(4)');

            if (!gradeElement || !strandElement) {
                console.error('Grade or Strand information is missing in the student card.');
                alert('Error: Grade or Strand information is missing. Please ensure the student has a grade and strand assigned.');
                return;
            }

            const grade = gradeElement.textContent.replace('Grade: ', '').trim();
            const strand = strandElement.textContent.replace('Strand: ', '').trim();

            const sectionDropdown = document.getElementById('section');
            sectionDropdown.innerHTML = '<option value="">-- Select Section --</option>'; // Clear existing options

            try {
                const response = await fetch(`fetch_sections_by_grade_and_strand.php?grade=${encodeURIComponent(grade)}&strand=${encodeURIComponent(strand)}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.success) {
                    result.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.section_ID;
                        option.textContent = section.section_Name;
                        sectionDropdown.appendChild(option);
                    });
                } else {
                    console.warn('Error fetching sections:', result.message);
                }
            } catch (error) {
                console.error('Error fetching sections:', error);
            }
        }

        function showModalNotification(message, isSuccess = true) {
            const modal = document.createElement('div');
            modal.className = 'notification-modal';

            const modalContent = document.createElement('div');
            modalContent.className = `notification-modal-content ${isSuccess ? 'success' : 'error'}`;
            modalContent.innerHTML = `
                <p>${message}</p>
            `;

            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            setTimeout(() => {
                modal.remove();
            }, 3000);
        }

        // Add styles for the modal notification
        const style = document.createElement('style');
        style.textContent = `
            .notification-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            }
            .notification-modal-content {
                background-color: white;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                animation: fadeInOut 3s ease-in-out;
                font-size: 1rem;
            }
            .notification-modal-content.success {
                border: 2px solid #28a745;
                color: #28a745;
            }
            .notification-modal-content.error {
                border: 2px solid #dc3545;
                color: #dc3545;
            }
            @keyframes fadeInOut {
                0% { opacity: 0; transform: scale(0.9); }
                10% { opacity: 1; transform: scale(1); }
                90% { opacity: 1; transform: scale(1); }
                100% { opacity: 0; transform: scale(0.9); }
            }
        `;
        document.head.appendChild(style);

        // Replace alert with showModalNotification
        async function assignGrade() {
            const grade = document.getElementById('grade').value;
            if (!grade) {
                showModalNotification('Please select a grade.', false);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'assign_grade');
            formData.append('studentid', currentStudentId);
            formData.append('grade', grade);

            try {
                const response = await fetch('student_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showModalNotification(result.message || 'Grade assigned successfully!');
                    // Hide the grade assignment form
                    document.getElementById('assignForms').style.display = 'none';
                    document.getElementById('assignGradeForm').style.display = 'none';
                } else {
                    showModalNotification(result.message || 'Failed to assign grade.');
                }
            } catch (error) {
    
            }
        }

        async function assignStrand() {
            const strand = document.getElementById('strand').value;
            if (!strand) {
                showModalNotification('Please select a strand.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'assign_strand');
            formData.append('studentid', currentStudentId);
            formData.append('strand', strand);

            try {
                const response = await fetch('student_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showModalNotification(result.message || 'Strand assigned successfully!');
                    updateStudentCard(currentStudentId, { strand, section: 'Not Assigned' });
                    document.getElementById('assignForms').style.display = 'none';
                } else {
                    showModalNotification(result.message || 'Failed to assign strand.');
                }
            } catch (error) {
                
            }
        }

        async function assignSection() {
            const section = document.getElementById('section').value;
            if (!section) {
                showModalNotification('Please select a section.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'assign_section');
            formData.append('studentid', currentStudentId);
            formData.append('section', section);

            try {
                const response = await fetch('student_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showModalNotification(result.message || 'Section assigned successfully!');
                    const sectionName = document.querySelector(`#section option[value="${section}"]`).textContent;
                    updateStudentCard(currentStudentId, { section: sectionName });
                    document.getElementById('assignForms').style.display = 'none';
                } else {
                    showModalNotification(result.message || 'Failed to assign section.');
                }
            } catch (error) {
                
            }
        }

        async function assignSubject() {
            const subject = document.getElementById('subject').value;
            if (!subject) {
                showModalNotification('Please select a subject.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'assign_subject');
            formData.append('studentid', currentStudentId);
            formData.append('subject', subject);

            try {
                const response = await fetch('student_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showModalNotification(result.message || 'Subject assigned successfully!');
                    const subjectsElement = document.getElementById(`subjects-${currentStudentId}`);
                    subjectsElement.textContent = result.updatedSubjects || 'Updated';
                } else {
                    showModalNotification(result.message || 'Failed to assign subject.');
                }
            } catch (error) {
                
            }
        }

        async function archiveStudent(studentId) {
            if (!confirm('Are you sure you want to archive this student?')) {
                return;
            }

            try {
                const response = await fetch('archive_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ studentId })
                });

                const result = await response.json();
                if (result.success) {
                    showModalNotification(result.message || 'Student archived successfully!');
                    const studentCard = document.getElementById(`student-${studentId}`);
                    if (studentCard) {
                        studentCard.remove(); // Remove the student card from the UI
                    }
                } else {
                    showModalNotification(result.message || 'Failed to archive student.');
                }
            } catch (error) {
               
            }
        }

        function updateStudentCard(studentId, updates) {
            const card = document.getElementById(`student-${studentId}`);
            if (card) {
                if (updates.grade) {
                    card.querySelector('p:nth-child(3)').textContent = `Grade: ${updates.grade}`;
                }
                if (updates.strand) {
                    const strandText = updates.strand === 'Not Assigned' ? 'Strand: Not Assigned (⚠️)' : `Strand: ${updates.strand}`;
                    card.querySelector('p:nth-child(4)').textContent = strandText;
                }
                if (updates.section) {
                    const sectionText = updates.section === 'Not Assigned' ? 'Section: Not Assigned (⚠️)' : `Section: ${updates.section}`;
                    card.querySelector('p:nth-child(5)').textContent = sectionText;
                }
            }
        }

        function moveStudentToAllStudents(studentId) {
            const studentCard = document.getElementById(`student-${studentId}`);
            if (studentCard) {
                const allStudentsContainer = document.getElementById('allStudents');
                if (!allStudentsContainer.contains(studentCard)) {
                    allStudentsContainer.appendChild(studentCard);
                }
            }
        }

        function moveStudentToSection(studentId, fromCategory, toCategory) {
            const studentCard = document.getElementById(`student-${studentId}`);
            if (studentCard) {
                document.getElementById(fromCategory).removeChild(studentCard);
                document.getElementById(toCategory).appendChild(studentCard);
            }
        }

        function removeStudentFromCategory(studentId, category) {
            const studentCard = document.getElementById(`student-${studentId}`);
            if (studentCard) {
                document.getElementById(category).removeChild(studentCard);
            }
        }

        async function refreshStudentCards() {
            try {
                const response = await fetch('fetch_realtime_students.php'); // Fetch updated student data
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const text = await response.text(); // Read response as text
                let result;
                try {
                    result = JSON.parse(text); // Attempt to parse JSON
                } catch (error) {
                    console.error('Invalid JSON response:', text); // Log the raw response for debugging
                    throw new Error('Failed to parse JSON response');
                }

                if (result.success) {
                    // Update or add student cards
                    const existingStudentIds = new Set();
                    document.querySelectorAll('.card').forEach(card => {
                        existingStudentIds.add(card.id.replace('student-', ''));
                    });

                    result.students.forEach(student => {
                        const cardId = `student-${student.shsstud_ID}`;
                        const card = document.getElementById(cardId);

                        if (card) {
                            // Update existing card
                            updateStudentCard(student.shsstud_ID, {
                                grade: student.student_grade || 'Not Assigned',
                                strand: student.strand_code || 'Not Assigned',
                                section: student.section_name || 'Not Assigned'
                            });
                        } else {
                            // Add new card
                            const newCard = createStudentCard(student);
                            document.getElementById('allStudents').appendChild(newCard);
                        }

                        // Mark student ID as processed
                        existingStudentIds.delete(student.shsstud_ID);
                    });

                    // Remove cards for students no longer in the data
                    existingStudentIds.forEach(studentId => {
                        const card = document.getElementById(`student-${studentId}`);
                        if (card) {
                            card.remove();
                        }
                    });
                } else {
                    console.warn('Error refreshing student cards:', result.message);
                }
            } catch (error) {
                console.error('Error refreshing student cards:', error);
            }
        }

        // Periodically refresh student cards every 3 seconds
        setInterval(refreshStudentCards, 3000);

        async function fetchStudentsWithoutSection() {
            try {
                const response = await fetch('fetch_students_without_section.php'); // Backend script to fetch students without a section
                const result = await response.json();

                if (result.success) {
                    const studentsWithoutSectionContainer = document.getElementById('studentsWithoutSection');
                    studentsWithoutSectionContainer.innerHTML = ''; // Clear the container

                    result.students.forEach(student => {
                        const listItem = document.createElement('li');
                        listItem.textContent = `${student.shstud_firstname} ${student.shstud_lastname}`;
                        studentsWithoutSectionContainer.appendChild(listItem);
                    });
                } else {
                    console.error('Error fetching students without section:', result.message);
                }
            } catch (error) {
                console.error('Error fetching students without section:', error);
            }
        }

        // Periodically fetch and update the "Students Without Section" section every 5 seconds
        setInterval(fetchStudentsWithoutSection, 5000);

        async function refreshStrandAndSection() {
            try {
                const response = await fetch('fetch_realtime_strand_section.php'); // Fetch updated strand and section data
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.success) {
                    result.data.forEach(student => {
                        const card = document.getElementById(`student-${student.shsstud_ID}`);
                        if (card) {
                            const strandElement = card.querySelector('p:nth-child(4)');
                            const sectionElement = card.querySelector('p:nth-child(5)');

                            if (strandElement) {
                                strandElement.textContent = `Strand: ${student.strand_code || 'Not Assigned'}`;
                            }
                            if (sectionElement) {
                                sectionElement.textContent = `Section: ${student.section_name || 'Not Assigned'}`;
                            }
                        }
                    });
                } else {
                    console.warn('Error refreshing strand and section:', result.message);
                }
            } catch (error) {
                console.error('Error refreshing strand and section:', error);
            }
        }

        // Periodically refresh strand and section every 5 seconds
        setInterval(refreshStrandAndSection, 300);

        async function refreshStudentGrades() {
            try {
                const response = await fetch('fetch_realtime_students.php'); // Fetch updated grade data
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.success) {
                    result.grades.forEach(student => {
                        const card = document.getElementById(`student-${student.shsstud_ID}`);
                        if (card) {
                            const gradeElement = card.querySelector('p:nth-child(3)');
                            if (gradeElement) {
                                gradeElement.textContent = `Grade: ${student.student_grade || 'Not Assigned'}`;
                            }
                        }
                    });
                } else {
                    console.warn('Error refreshing student grades:', result.message);
                }
            } catch (error) {
                console.error('Error refreshing student grades:', error);
            }
        }

        // Periodically refresh student grades every 5 seconds
        setInterval(refreshStudentGrades, 300);

        async function refreshStudentSections() {
            try {
                const response = await fetch('fetch_realtime_section.php'); // Fetch updated section data
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.success) {
                    result.sections.forEach(student => {
                        const card = document.getElementById(`student-${student.shsstud_ID}`);
                        if (card) {
                            const sectionElement = card.querySelector('p:nth-child(5)');
                            if (sectionElement) {
                                sectionElement.textContent = `Section: ${student.section_name || 'Not Assigned'}`;
                            }
                        }
                    });
                } else {
                    console.warn('Error refreshing student sections:', result.message);
                }
            } catch (error) {
                console.error('Error refreshing student sections:', error);
            }
        }

        // Periodically refresh student sections every 5 seconds
        setInterval(refreshStudentSections, 5000);

        // Burger menu functionality
        document.addEventListener('DOMContentLoaded', () => {
            const burgerMenu = document.getElementById('burger-menu');
            const sideMenu = document.getElementById('side-menu');
            const closeMenuButton = document.getElementById('close-menu');

            // Open the side menu when the burger menu is clicked
            burgerMenu.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent click from propagating to the window
                sideMenu.style.display = 'block';
            });

            // Close the side menu when the close button is clicked
            closeMenuButton.addEventListener('click', () => {
                sideMenu.style.display = 'none';
            });

            // Close the side menu when clicking outside of it
            window.addEventListener('click', (event) => {
                if (!sideMenu.contains(event.target) && event.target !== burgerMenu) {
                    sideMenu.style.display = 'none';
                }
            });
        });

        function openAssignFormsModal(formId, studentId) {
            currentStudentId = studentId;
            document.getElementById('assignFormsModal').style.display = 'flex';
            document.getElementById('assignGradeForm').style.display = 'none';
            document.getElementById('assignStrandForm').style.display = 'none';
            document.getElementById('assignSectionForm').style.display = 'none';
            document.getElementById('assignSubjectForm').style.display = 'none';

            document.getElementById(formId).style.display = 'block';
        }

        function closeAssignFormsModal() {
            document.getElementById('assignFormsModal').style.display = 'none';
        }

        function showAssignSubjectForm(studentId) {
            openAssignFormsModal('assignSubjectForm', studentId);
        }

        function showAssignSchoolYearForm(studentId) {
            currentStudentId = studentId;
            document.getElementById('assignSchoolYearModal').style.display = 'flex';
        }

        function closeAssignSchoolYearModal() {
            document.getElementById('assignSchoolYearModal').style.display = 'none';
        }

        async function assignSchoolYear() {
            const schoolYear = document.getElementById('school_year').value;
            const schoolEnd = document.getElementById('school_end').value;

            if (!schoolYear || !schoolEnd) {
                showModalNotification('Please select both start and end dates.', false);
                return;
            }

            const startDate = new Date(schoolYear);
            const endDate = new Date(schoolEnd);

            // Validate that the dates are at most 1 year and 1 month apart
            const maxDifference = 13; // 12 months + 1 month
            const differenceInMonths = (endDate.getFullYear() - startDate.getFullYear()) * 12 + (endDate.getMonth() - startDate.getMonth());

            if (differenceInMonths > maxDifference || differenceInMonths < 0) {
                showModalNotification('The school year must be at most 1 year and 1 month apart.', false);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'assign_school_year'); // Ensure the action matches the backend handler
            formData.append('studentid', currentStudentId);
            formData.append('school_year', schoolYear);
            formData.append('school_end', schoolEnd);

            try {
                const response = await fetch('student_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showModalNotification(result.message || 'School year assigned successfully!');
                    closeAssignSchoolYearModal();
                } else {
                    showModalNotification(result.message || 'Failed to assign school year.', false);
                }
            } catch (error) {
                console.error('Error assigning school year:', error);
                showModalNotification('An error occurred while assigning the school year.', false);
            }
        }

        async function fetchSchoolYear(studentId) {
            try {
                const response = await fetch(`fetch_school_year.php?studentid=${studentId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.success) {
                    document.getElementById(`school-year-${studentId}`).textContent = result.school_year || 'Not Assigned';
                    document.getElementById(`school-end-${studentId}`).textContent = result.school_end || 'Not Assigned';
                } else {
                    console.warn('Error fetching school year:', result.message);
                }
            } catch (error) {
                console.error('Error fetching school year:', error);
            }
        }

        function refreshSchoolYears() {
            const studentCards = document.querySelectorAll('.card.all-students-card');
            studentCards.forEach(card => {
                const studentId = card.id.replace('student-', '');
                fetchSchoolYear(studentId);
            });
        }

        // Refresh school year data every 5 seconds
        setInterval(refreshSchoolYears, 5000);
        refreshSchoolYears(); // Initial fetch

        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        let studentToArchive = null;

        function showArchiveModal(studentId) {
            studentToArchive = studentId;
            document.getElementById('archiveModal').style.display = 'flex';
        }

        function closeArchiveModal() {
            studentToArchive = null;
            document.getElementById('archiveModal').style.display = 'none';
        }

        document.getElementById('confirmArchiveButton').addEventListener('click', async () => {
            if (!studentToArchive) return;

            try {
                const response = await fetch('archive_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ studentId: studentToArchive })
                });

                const result = await response.json();
                if (result.success) {
                    showModalNotification(result.message || 'Student archived successfully!');
                    const studentCard = document.getElementById(`student-${studentToArchive}`);
                    if (studentCard) {
                        studentCard.remove(); // Remove the student card from the UI
                    }
                } else {
                    showModalNotification(result.message || 'Failed to archive student.', false);
                }
            } catch (error) {
                console.error('Error archiving student:', error);
                showModalNotification('An error occurred while archiving the student.', false);
            } finally {
                closeArchiveModal();
            }
        });
    </script>
</body>
</html>