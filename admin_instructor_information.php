<?php
// Start the session at the very top of the file
session_start();

// Ensure the session is active and the CSRF token is set
if (session_status() !== PHP_SESSION_ACTIVE) {
    die('Session not started properly.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a CSRF token
}

// Prevent browser caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Content-Type: text/html; charset=UTF-8");

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

// Get the teacher ID from the query string
$teacherId = $_GET['teacherid'] ?? null;

if (!$teacherId) {
    die('Error: No teacher ID provided.');
}

// Fetch the teacher data from the database
$sql = "SELECT * FROM teachrinf WHERE teacherid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $teacherId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Error: Teacher not found.');
}

$teacher = $result->fetch_assoc();
$stmt->close();

// Fetch assigned sections
$sectionsSql = "SELECT s.section_Name 
                FROM tblsecteacher st
                INNER JOIN tblshssection s ON st.section_ID = s.section_ID
                WHERE st.teacher_ID = ?";
$sectionsStmt = $conn->prepare($sectionsSql);
$sectionsStmt->bind_param('s', $teacherId);
$sectionsStmt->execute();
$sectionsResult = $sectionsStmt->get_result();

$sections = [];
while ($row = $sectionsResult->fetch_assoc()) {
    $sections[] = $row['section_Name'];
}
$sectionsStmt->close();

// Handle form submission for updating teacher information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $firstName = $_POST['first-name'];
    $middleName = $_POST['middle-name'];
    $lastName = $_POST['last-name'];
    $field = $_POST['field'];
    $strand = $_POST['strand'];
    $grade = $_POST['grade'];
    $email = $_POST['email'];
    $contactNumber = $_POST['contact-number'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $currentAddress = $_POST['current-address'];
    $permanentAddress = $_POST['permanent-address'];
    $extensions = $_POST['extensions'];
    $sex = $_POST['sex'];

    // Handle picture upload
    $imageData = null;
    if (isset($_FILES['teacher-picture']) && $_FILES['teacher-picture']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['teacher-picture']['tmp_name']);
    }

    if ($imageData !== null) {
        $updateSql = "UPDATE teachrinf 
                      SET teachername = ?, teachermidd = ?, teacherlastname = ?, teacherfield = ?, strand_ID = ?, grade = ?, 
                          teacher_email = ?, teacher_contactno = ?, teacher_dob = ?, teacher_age = ?, teacher_ca = ?, 
                          teacher_padd = ?, teacher_extensions = ?, teacher_sex = ?, teacher_pfp = ?
                      WHERE teacherid = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param(
            'ssssssssssssssss',
            $firstName,
            $middleName,
            $lastName,
            $field,
            $strand,
            $grade,
            $email,
            $contactNumber,
            $dob,
            $age,
            $currentAddress,
            $permanentAddress,
            $extensions,
            $sex,
            $imageData,
            $teacherId
        );
    } else {
        $updateSql = "UPDATE teachrinf 
                      SET teachername = ?, teachermidd = ?, teacherlastname = ?, teacherfield = ?, strand_ID = ?, grade = ?, 
                          teacher_email = ?, teacher_contactno = ?, teacher_dob = ?, teacher_age = ?, teacher_ca = ?, 
                          teacher_padd = ?, teacher_extensions = ?, teacher_sex = ?
                      WHERE teacherid = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param(
            'sssssssssssssss',
            $firstName,
            $middleName,
            $lastName,
            $field,
            $strand,
            $grade,
            $email,
            $contactNumber,
            $dob,
            $age,
            $currentAddress,
            $permanentAddress,
            $extensions,
            $sex,
            $teacherId
        );
    }

    if ($updateStmt->execute()) {
        echo '<script>alert("Teacher information updated successfully!");</script>';
        // Refresh the page to reflect updated data
        header("Location: " . $_SERVER['PHP_SELF'] . "?teacherid=" . $teacherId);
        exit();
    } else {
        echo '<script>alert("Error updating teacher information.");</script>';
    }

    $updateStmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #003366;
            padding: 10px 20px;
        }
        header .logo img {
            height: 50px;
        }
        header nav a {
            text-decoration: none;
            color: white;
            font-size: 16px;
        }
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-top: 50px;
        }
        .main-instructor {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 800px;
        }
        .main-instructor h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .form-group ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .form-group ul li {
            margin: 5px 0;
        }
        .submit-btn {
            text-align: center;
            margin-top: 20px;
        }
        .submit-btn button {
            background-color: #003366;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn button:hover {
            background-color: #00509e;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo">
        </div>
        <nav>
            <a href="admin_dashboard.php">Back</a>
        </nav>
    </header>
    <div class="main-container">
        <div class="main-instructor">
            <h2>Instructor Information</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="teacher-id">Teacher ID</label>
                        <input type="text" id="teacher-id" value="<?php echo htmlspecialchars($teacher['teacherid']); ?>" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" value="<?php echo htmlspecialchars($teacher['teachername']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="middle-name" value="<?php echo htmlspecialchars($teacher['teachermidd']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" value="<?php echo htmlspecialchars($teacher['teacherlastname']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="field">Field</label>
                        <input type="text" id="field" name="field" value="<?php echo htmlspecialchars($teacher['teacherfield']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="strand">Strand</label>
                        <input type="text" id="strand" name="strand" value="<?php echo htmlspecialchars($teacher['strand_ID'] ?? 'Not Assigned'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="grade">Grade</label>
                        <input type="text" id="grade" name="grade" value="<?php echo htmlspecialchars($teacher['grade'] ?? 'Not Assigned'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($teacher['teacher_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact-number">Contact Number</label>
                        <input type="text" id="contact-number" name="contact-number" value="<?php echo htmlspecialchars($teacher['teacher_contactno'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($teacher['teacher_dob'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="text" id="age" name="age" value="<?php echo htmlspecialchars($teacher['teacher_age'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="current-address">Current Address</label>
                        <input type="text" id="current-address" name="current-address" value="<?php echo htmlspecialchars($teacher['teacher_ca'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="permanent-address">Permanent Address</label>
                        <input type="text" id="permanent-address" name="permanent-address" value="<?php echo htmlspecialchars($teacher['teacher_padd'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="extensions">Extensions</label>
                        <input type="text" id="extensions" name="extensions" value="<?php echo htmlspecialchars($teacher['teacher_extensions'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="sex">Sex</label>
                        <input type="text" id="sex" name="sex" value="<?php echo htmlspecialchars($teacher['teacher_sex'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="assigned-sections">Assigned Sections</label>
                        <?php if (!empty($sections)): ?>
                            <ul>
                                <?php foreach ($sections as $section): ?>
                                    <li><?php echo htmlspecialchars($section); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>None</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="teacher-picture">Teacher Picture</label>
                        <?php if (!empty($teacher['teacher_pfp'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($teacher['teacher_pfp']); ?>" alt="Teacher Picture" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            <p>No picture available</p>
                        <?php endif; ?>
                        <input type="file" id="teacher-picture" name="teacher-picture" accept="image/*">
                    </div>
                </div>
                <div class="submit-btn">
                    <button type="submit" name="update">Update</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>