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

// Logout function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    session_destroy(); // Destroy the session
    header("Location: adminLogin.php"); // Redirect to admin login page
    exit;
}

// Database connection credentials
$username = "root"; 
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage()); // Log error
    die("ERROR: Could not connect. Please try again later.");
}

// Fetch strands from the database
$strands = [];
try {
    $sql = "SELECT strand_ID, strand_code FROM tblstrand";
    $stmt = $pdo->prepare($sql); // Use prepared statement
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $strands[$row['strand_ID']] = $row['strand_code'];
    }
} catch (PDOException $e) {
    error_log("Error fetching strands: " . $e->getMessage()); // Log error
    die("ERROR: Could not fetch strands. Please try again later.");
}

$message = ""; // Initialize message variable

// Handle adding a new section
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["strand_ID"]) && isset($_POST["section_name"]) && isset($_POST["shsgrade"])) {
    $strand_ID = trim($_POST["strand_ID"]);
    $section_name = trim($_POST["section_name"]);
    $shsgrade = trim($_POST["shsgrade"]);

    if (!empty($strand_ID) && !empty($section_name) && !empty($shsgrade)) {
        try {
            // Check if the section already exists
            $checkSql = "SELECT COUNT(*) FROM tblshssection WHERE section_name = :section_name";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':section_name', $section_name, PDO::PARAM_STR);
            $checkStmt->execute();
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                $message = "Section '$section_name' already exists.";
            } else {
                // Insert the new section
                $sql = "INSERT INTO tblshssection (strand_ID, section_name, shsgrade) VALUES (:strand_ID, :section_name, :shsgrade)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':strand_ID', $strand_ID, PDO::PARAM_INT);
                $stmt->bindParam(':section_name', $section_name, PDO::PARAM_STR);
                $stmt->bindParam(':shsgrade', $shsgrade, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $strand_code = $strands[$strand_ID] ?? "Unknown Strand";
                    $message = "Section '$section_name' under Strand '$strand_code' for Grade '$shsgrade' has been successfully added.";
                } else {
                    $message = "Error adding section.";
                }
            }
        } catch (PDOException $e) {
            error_log("Error adding section: " . $e->getMessage()); // Log error
            $message = "Database error: Please try again later.";
        }

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
        exit;
    } else {
        $message = "Please fill in all fields.";
    }
}

// Handle assign strand action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];

    if ($action === "assign_strand" && isset($_POST["teacherid"]) && isset($_POST["assign_strand_id"])) {
        $teacherid = trim($_POST["teacherid"]);
        $strand_ID = trim($_POST["assign_strand_id"]);

        if (!empty($teacherid) && !empty($strand_ID)) {
            try {
                // Update only the strand_ID in teachrinf
                $sql = "UPDATE teachrinf SET strand_ID = :strand_ID WHERE teacherid = :teacherid";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':strand_ID', $strand_ID, PDO::PARAM_INT);
                $stmt->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $message = "Strand updated successfully!";
                } else {
                    $message = "Error updating strand.";
                }
            } catch (PDOException $e) {
                error_log("Error updating strand: " . $e->getMessage()); // Log error
                $message = "Database error: Please try again later.";
            }

            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
            exit;
        } else {
            $message = "Please fill in all fields.";
        }
    }

    if ($action === "assign_section" && isset($_POST["teacherid"]) && isset($_POST["section_ID"])) {
        $teacherid = trim($_POST["teacherid"]); // Ensure teacherid is treated as a full string
        $section_ID = (int) trim($_POST["section_ID"]); // Treat section_ID as an integer

        if (!empty($teacherid) && !empty($section_ID)) {
            try {
                // Check if the section is already assigned to the teacher
                $checkSql = "SELECT COUNT(*) FROM tblsecteacher WHERE teacher_ID = :teacherid AND section_ID = :section_ID";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->bindParam(':teacherid', $teacherid, PDO::PARAM_STR); // Bind as string to ensure full value is read
                $checkStmt->bindParam(':section_ID', $section_ID, PDO::PARAM_INT);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn();

                if ($exists) {
                    $message = "Section is already assigned to this teacher.";
                } else {
                    // Assign the section to the teacher
                    $sql = "INSERT INTO tblsecteacher (teacher_ID, section_ID) VALUES (:teacherid, :section_ID)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':teacherid', $teacherid, PDO::PARAM_STR); // Bind as string to ensure full value is read
                    $stmt->bindParam(':section_ID', $section_ID, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $message = "Section assigned successfully!";
                    } else {
                        $message = "Error assigning section.";
                    }
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
            }
        } else {
            $message = "Please fill in all fields.";
        }

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
        exit;
    }

    if ($action === "remove_section" && isset($_POST["secteacher_ID"])) {
        $secteacher_ID = (int) trim($_POST["secteacher_ID"]); // Treat secteacher_ID as an integer

        if (!empty($secteacher_ID)) {
            try {
                // Ensure the record exists in the database
                $checkSql = "SELECT COUNT(*) FROM tblsecteacher WHERE secteacher_ID = :secteacher_ID";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->bindParam(':secteacher_ID', $secteacher_ID, PDO::PARAM_INT);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn();

                if ($exists) {
                    // Proceed to delete the section assignment
                    $sql = "DELETE FROM tblsecteacher WHERE secteacher_ID = :secteacher_ID";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':secteacher_ID', $secteacher_ID, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $message = "Section removed successfully!";
                    } else {
                        $message = "Error removing section.";
                    }
                } else {
                    $message = "No such section assignment exists.";
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
            }
        } else {
            $message = "Please provide a valid section assignment ID.";
        }

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
        exit;
    }
}

// Handle assign grade action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] === "assign_grade") {
    $teacherid = trim($_POST["teacherid"]);
    $grade = trim($_POST["grade"]);

    if (!empty($teacherid) && !empty($grade)) {
        try {
            $sql = "UPDATE teachrinf SET grade = :grade WHERE teacherid = :teacherid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':grade', $grade);
            $stmt->bindParam(':teacherid', $teacherid);

            if ($stmt->execute()) {
                $message = "Grade assigned successfully!";
            } else {
                $message = "Error assigning grade.";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    } else {
        $message = "Please fill in all fields.";
    }

    // Redirect to avoid form resubmission and display the message
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
    exit;
}

// Handle assign subject action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] === "assign_subject") {
    $teacherid = trim($_POST["teacherid"]);
    $subject = trim($_POST["subject"]);

    if (!empty($teacherid) && !empty($subject)) {
        try {
            // Fetch the subject name for updating teacherfield
            $sqlFetchSubject = "SELECT shssub_name FROM tblshssubject WHERE shssub_ID = :subject";
            $stmtFetchSubject = $pdo->prepare($sqlFetchSubject);
            $stmtFetchSubject->bindParam(':subject', $subject, PDO::PARAM_INT);
            $stmtFetchSubject->execute();
            $subjectName = $stmtFetchSubject->fetchColumn();

            if ($subjectName) {
                // Update the teacherfield in teachrinf with the new subject
                $sqlUpdateField = "UPDATE teachrinf SET teacherfield = :teacherfield WHERE teacherid = :teacherid";
                $stmtUpdateField = $pdo->prepare($sqlUpdateField);
                $stmtUpdateField->bindParam(':teacherfield', $subjectName, PDO::PARAM_STR);
                $stmtUpdateField->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);

                if ($stmtUpdateField->execute()) {
                    $message = "Subject assigned successfully!";
                } else {
                    $message = "Error assigning subject.";
                }
            } else {
                $message = "Invalid subject selected.";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    } else {
        $message = "Please fill in all fields.";
    }

    // Redirect to avoid form resubmission and display the message
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
    exit;
}

// Handle add teacher action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] === "add_teacher") {
    $teacherData = json_decode(file_get_contents('php://input'), true);

    if (!empty($teacherData)) {
        try {
            // Insert the new teacher into the database
            $sql = "INSERT INTO teachrinf (teachername, teachermidd, teacherlastname, teacherfield, strand_ID, grade) 
                    VALUES (:teachername, :teachermidd, :teacherlastname, :teacherfield, :strand_ID, :grade)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':teachername', $teacherData['teachername'], PDO::PARAM_STR);
            $stmt->bindParam(':teachermidd', $teacherData['teachermidd'], PDO::PARAM_STR);
            $stmt->bindParam(':teacherlastname', $teacherData['teacherlastname'], PDO::PARAM_STR);
            $stmt->bindParam(':teacherfield', $teacherData['teacherfield'], PDO::PARAM_STR);
            $stmt->bindParam(':strand_ID', $teacherData['strand_ID'], PDO::PARAM_STR);
            $stmt->bindParam(':grade', $teacherData['grade'], PDO::PARAM_STR);
            $stmt->execute();

            $teacherId = $pdo->lastInsertId();

            // Prepare the teacher card data
            $teacherCard = [
                'teacherid' => $teacherId,
                'teachername' => $teacherData['teachername'],
                'teachermidd' => $teacherData['teachermidd'],
                'teacherlastname' => $teacherData['teacherlastname'],
                'teacherfield' => $teacherData['teacherfield'],
                'strand_ID' => $teacherData['strand_ID'],
                'grade' => $teacherData['grade'],
                'sections' => []
            ];

            echo json_encode(['success' => true, 'message' => 'Teacher added successfully!', 'teacherCard' => $teacherCard]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid teacher data.']);
    }

    exit; // Stop further script execution
}

// Handle archive teacher action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] === "archive_teacher") {
    $teacherid = trim($_POST["teacherid"]);

    if (!empty($teacherid)) {
        try {
            $sql = "UPDATE teachrinf SET teachstat = 'ARCHIVED' WHERE teacherid = :teacherid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':teacherid', $teacherid, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Teacher archived successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive teacher.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid teacher ID.']);
    }
    exit;
}

// Fetch teachers with teachstat as NULL
$teachers = [];
try {
    $sql = "SELECT t.teacherid, t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield, t.strand_ID, t.grade 
            FROM teachrinf t
            WHERE t.teachstat IS NULL -- Fetch only teachers with teachstat as NULL
            ORDER BY t.teachername ASC";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $teacherid = (int) $row['teacherid'];
            if (!isset($teachers[$teacherid])) {
                $teachers[$teacherid] = [
                    'info' => [
                        'teachername' => $row['teachername'],
                        'teachermidd' => $row['teachermidd'],
                        'teacherlastname' => $row['teacherlastname'],
                        'teacherfield' => $row['teacherfield'],
                        'strand_ID' => $row['strand_ID'],
                        'grade' => $row['grade']
                    ],
                    'sections' => []
                ];
            }
        }
    }

    // Fetch sections assigned to each teacher
    foreach (array_keys($teachers) as $teacherid) {
        $sectionSql = "SELECT DISTINCT st.secteacher_ID, s.section_Name 
                       FROM tblsecteacher st
                       INNER JOIN tblshssection s ON st.section_ID = s.section_ID
                       WHERE st.teacher_ID = :teacherid";
        $sectionStmt = $pdo->prepare($sectionSql);
        $sectionStmt->bindParam(':teacherid', $teacherid, PDO::PARAM_INT);
        $sectionStmt->execute();
        $sections = $sectionStmt->fetchAll(PDO::FETCH_ASSOC);

        $teachers[$teacherid]['sections'] = $sections;
    }
} catch (PDOException $e) {
    die("ERROR: Could not execute $sql. " . $e->getMessage());
}

// Display message from redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

$sections = [];
try {
    $sql = "SELECT section_ID, section_Name, strand_ID, shsgrade 
            FROM tblshssection";
    $stmt = $pdo->query($sql);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not execute $sql. " . $e->getMessage());
}

unset($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="studentPROFILE.css">
    <title>Admin Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            max-width: 100%;
            padding-top: 0; /* Changed from 80px to 0 to remove unused space */
        }
        .main-header {
            position: relative; /* Changed from fixed to relative */
            top: 0;
            left: 0;
            width: 100%;
            color: white;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px; /* Normal padding */
        }
        .logo-section img {
            height: 60px; /* Normal height for the logo */
        }
        .user-section {
            display: flex;
            align-items: center;
        }
        .user-role {
            margin-right: 20px; /* Normal margin */
            font-weight: bold;
        }
        .logout-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px; /* Normal padding */
            border-radius: 5px;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #0056b3;
        }
        .sub-main {
            display: flex;
            align-items: center;
            padding: 10px 20px; /* Normal padding */
        }
        .menu-icon {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 5px; /* Normal gap */
        }
        .menu-icon .line {
            width: 30px; /* Normal width */
            height: 3px;
            background-color: white;
        }
        .home-section {
            display: flex;
            align-items: center;
            margin-left: 20px; /* Normal margin */
        }
        .home-section img {
            height: 30px; /* Normal height */
            margin-right: 10px; /* Normal margin */
        }
        .dropdown {
            display: none;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .img1 {
            height: 50px; /* Adjusted height for better alignment */
            margin-right: 10px;
        }
        .site-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }
        .header-right {
            display: flex;
            align-items: center;
        }
        .flex-container {
            display: flex;
            flex-wrap: wrap;
        }
        .card {
            background-color: #f2f2f2;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin: 10px;
            width: 200px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }
        .card h3 {
            margin-top: 0;
        }
        .navButton {
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
        }
        .navButton:hover {
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        .dashboard-actions {
            display: flex;
            justify-content: center; /* Center the buttons horizontally */
            gap: 20px; /* Add spacing between buttons */
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .message {
            margin: 20px 0;
            padding: 10px;
            border-radius: 5px;
            background-color: #f0f8ff;
            color: #333;
            font-size: 1rem;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .add-section-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
            width: 50%; /* Adjust width as needed */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .add-section-container h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
        }
        .add-section-container form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .add-section-container label {
            width: 100%;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .add-section-container select,
        .add-section-container input[type="text"],
        .add-section-container button {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .add-section-container button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .add-section-container button:hover {
            background-color: #0056b3;
        }
        .dashboard-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            margin: 0 auto;
            padding: 20px;
            margin-top: 100px; /* Reduce free space above the admin dashboard */
            width: 80%; /* Adjust width as needed */
            max-width: 1200px; /* Limit maximum width */
        }
        .dashboard-container h1 {
            text-align: center;
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }
        .dashboard-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .dashboard-actions .btn {
            padding: 10px 20px;
            font-size: 1rem;
        }
        .navButton {
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
        }
        .navButton:hover {
            background-color: #0056b3;
        }
        .form-container {
            display: none; /* Initially hidden */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001; /* Ensure it appears above the blurred background */
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 50%; /* Adjust width as needed */
            max-width: 500px;
        }
        .form-container h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
            text-align: center;
        }
        .form-container form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .form-container label {
            width: 100%;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-container select,
        .form-container button {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-container button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .blurred-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
            backdrop-filter: blur(5px); /* Apply blur effect */
            z-index: 1000; /* Ensure it appears below the form */
            display: none; /* Initially hidden */
        }
        .logout-button {
            margin-right: 50px; /* Add margin to move the button to the left */
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        .logout-button:hover {
            background-color: #0056b3;
        }
        .search-container {
            position: relative;
            width: 80%;
            margin: 20px auto;
        }
        .search-container input {
            width: 100%;
            padding: 10px 40px 10px 20px;
            border: 2px solid #000;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
        }
        .search-container .search-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: #000;
            cursor: pointer;
        }
        #teachersContainer {
            max-height: 500px; /* Set a maximum height for the container */
            overflow-y: auto; /* Enable vertical scrolling */
            padding: 10px; /* Add padding inside the container */
            background-color: #f9f9f9; /* Light background color */
            border: 1px solid #ddd; /* Border color */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add box shadow */
        }

        #teachersContainer::-webkit-scrollbar {
            width: 8px;
        }

        #teachersContainer::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 4px;
        }

        #teachersContainer::-webkit-scrollbar-thumb:hover {
            background-color: #aaa;
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
            background-color: #007bff;
            color: white;
        }

        .archive-modal-content .confirm-archive:hover {
            background-color: #0056b3;
        }

        .archive-modal-content .cancel-archive {
            background-color: #f2f2f2;
            color: #333;
        }

        .archive-modal-content .cancel-archive:hover {
            background-color: #e0e0e0;
        }
    </style>

    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <h3>Are you sure you want to log out?</h3>
            <form action="" method="POST" style="display: inline;">
                <button type="submit" name="logout" class="confirm-logout">Yes</button>
            </form>
            <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
        </div>
    </div>

    <div id="archiveModal" class="archive-modal">
        <div class="archive-modal-content">
            <h3>Are you sure you want to archive this teacher?</h3>
            <button id="confirmArchiveButton" class="confirm-archive">Yes</button>
            <button class="cancel-archive" onclick="closeArchiveModal()">No</button>
        </div>
    </div>

    <script>
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        let teacherToArchive = null;

        function showArchiveModal(teacherId) {
            teacherToArchive = teacherId;
            document.getElementById('archiveModal').style.display = 'flex';
        }

        function closeArchiveModal() {
            teacherToArchive = null;
            document.getElementById('archiveModal').style.display = 'none';
        }

        document.getElementById('confirmArchiveButton').addEventListener('click', () => {
            if (!teacherToArchive) return;

            const formData = new FormData();
            formData.append('action', 'archive_teacher');
            formData.append('teacherid', teacherToArchive);

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const teacherCard = document.getElementById(`teacher-${teacherToArchive}`);
                    if (teacherCard) {
                        teacherCard.remove();
                    }
                    showPopupMessage(data.message, 'success');
                } else {
                    showPopupMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showPopupMessage('Failed to archive teacher.', 'error');
            })
            .finally(() => {
                closeArchiveModal();
            });
        });
    </script>
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <div class="logo-section">
            <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
        </div>
        <div class="user-section">
            <span class="user-role">Admin</span>
            <form action="" method="POST" style="display: inline;">
                <button type="button" class="logout-button" onclick="showLogoutModal()">Logout</button>
            </form>
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
                <span class="home-text">Dashboard</span>
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
            <li class="menu-section-title">GRADES</li>
            <li><a href="adminGrade.php" class="menu-item">Manage Grades</a></li>
        </ul>
    </nav>
</header>
  <div class="dashboard-container">
      <h1>Admin Dashboard</h1>
      <div class="dashboard-actions">
          <!-- Add a button to navigate to view_sections_by_strand.php -->
          <a href="view_sections_by_strand.php" class="btn">View Sections by Strand</a>
      </div>
      <div class="add-section-container">
          <h2>Add Section</h2>
          <?php if (!empty($message)): ?>
              <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                  <?php echo htmlspecialchars($message); ?>
              </div>
          <?php endif; ?>
          <form id="add-section-form" method="POST">
              <label for="strand">Select Strand:</label>
              <select id="strand" name="strand_ID" required>
                  <option value="">-- Select Strand --</option>
                  <?php foreach ($strands as $strand_ID => $strand_code): ?>
                      <option value="<?php echo htmlspecialchars($strand_ID); ?>">
                          <?php echo htmlspecialchars($strand_code); ?>
                      </option>
                  <?php endforeach; ?>
              </select>
              <label for="section-name">Section Name:</label>
              <input type="text" id="section-name" name="section_name" required>
              <label for="shsgrade">Select Grade:</label>
              <select id="shsgrade" name="shsgrade" required>
                  <option value="">-- Select Grade --</option>
                  <option value="GRADE 11">GRADE 11</option>
                  <option value="GRADE 12">GRADE 12</option>
              </select>
              <button type="submit">Add Section</button>
          </form>
      </div>
      <h2>Registered Teachers</h2>
      <div class="search-container">
          <input type="text" id="teacherSearch" placeholder="Search teachers by name..." onkeypress="handleEnter(event)">
          <span class="search-icon" onclick="searchTeachers()">&#128269;</span>
      </div>
      <div style="text-align: center; margin-top: 10px;">
          <button class="btn" onclick="viewArchivedTeachers()">View Archived Teachers</button>
      </div>
      <script>
          function viewArchivedTeachers() {
              window.location.href = 'archived_teachers.php'; // Redirect to a new page for archived teachers
          }
      </script>
      <div class="flex-container" id="teachersContainer">
          <?php
          foreach ($teachers as $teacherid => $teacher) {
              echo "<div class='card' id='teacher-" . htmlspecialchars($teacherid) . "'>";
              echo "<h3>" . htmlspecialchars($teacher["info"]["teachername"]) . " " . htmlspecialchars($teacher["info"]["teachermidd"]) . " " . htmlspecialchars($teacher["info"]["teacherlastname"]) . "</h3>";
              echo "<p>Field: " . htmlspecialchars($teacher["info"]["teacherfield"]) . "</p>";
              
              // Add strand indicator
              $strand_ID = $teacher["info"]["strand_ID"] ?? null;
              $strand_code = $strand_ID && isset($strands[$strand_ID]) ? $strands[$strand_ID] : "Not Assigned";
              echo "<p>Strand: " . htmlspecialchars($strand_code) . "</p>";
              
              // Add grade indicator
              $grade = $teacher["info"]["grade"] ?? "Not Assigned";
              echo "<p>Grade: " . htmlspecialchars($grade) . "</p>";
              
              // Display assigned sections
              if (!empty($teacher["sections"])) {
                  echo "<p>Assigned Sections: ";
                  foreach ($teacher["sections"] as $section) {
                      echo htmlspecialchars($section["section_Name"]) . ", ";
                  }
                  echo "</p>";
              } else {
                  echo "<p>Assigned Sections: None</p>";
              }
              
              echo "<div class='menu' onclick='toggleDropdown(this)'>⋮</div>";
              echo "<div class='dropdown'>";
              echo "<a href='#' onclick='showAssignGradeForm(" . htmlspecialchars($teacherid) . ")'>Assign Grade</a>";
              echo "<a href='#' onclick='showAssignStrandForm(" . htmlspecialchars($teacherid) . ")'>Assign Strand</a>";
              echo "<a href='#' onclick='showSectionForm(" . htmlspecialchars($teacherid) . ")'>Assign Section</a>";
              if ($strand_code !== "Not Assigned" && $grade !== "Not Assigned") {
                  echo "<a href='#' onclick='showAssignSubjectForm(" . htmlspecialchars($teacherid) . ", \"" . htmlspecialchars($strand_ID) . "\", \"" . htmlspecialchars($grade) . "\")'>Assign Subject</a>";
              }
              echo "<a href='#' onclick='showRemoveSectionForm(" . htmlspecialchars($teacherid) . ", " . json_encode($teacher["sections"]) . ")'>Remove Section</a>";
              echo "<a href='manage_schedule.php?teacherid=" . htmlspecialchars($teacherid) . "' target='_blank'>Manage Schedule</a>";
              echo "<a href='admin_instructor_information.php?teacherid=" . htmlspecialchars($teacherid) . "'>View Teacher Information</a>";
              echo "<a href='#' onclick='showArchiveModal(" . htmlspecialchars($teacherid) . ")'>Archive Teacher</a>";
              echo "</div>";
              echo "</div>";
          }
          ?>
      </div>
  </div>

    <div class="blurred-background" id="blurredBackground"></div>

    <div id="sectionForm" class="form-container">
        <h2>Assign Section to Teacher</h2>
        <form method="POST">
            <input type="hidden" name="action" value="assign_section">
            <input type="hidden" id="teacherid" name="teacherid">
            <label for="secname">Section Name:</label>
            <select id="secname" name="section_ID" required>
                <option value="">-- Select Section --</option>
                <!-- Options will be dynamically populated -->
            </select>
            <button type="submit">Assign Section</button>
            <button type="button" onclick="closeSectionForm()">Cancel</button>
        </form>
    </div>

    <div id="removeSectionForm" class="form-container">
        <h2>Remove Section from Teacher</h2>
        <form method="POST">
            <input type="hidden" name="action" value="remove_section">
            <input type="hidden" id="secteacher_ID" name="secteacher_ID">
            <label for="remove_secname">Section Name:</label>
            <select id="remove_secname" required>
                <option value="">-- Select Section --</option>
                <!-- Options will be dynamically populated -->
            </select>
            <button type="submit">Remove Section</button>
            <button type="button" onclick="closeRemoveSectionForm()">Cancel</button>
        </form>
    </div>

    <div id="assignStrandForm" class="form-container">
        <h2>Assign Strand to Teacher</h2>
        <form method="POST">
            <input type="hidden" name="action" value="assign_strand">
            <input type="hidden" id="assign_strand_teacherid" name="teacherid">
            <label for="assign_strand_id">Strand:</label>
            <select id="assign_strand_id" name="assign_strand_id" required>
                <option value="">-- Select Strand --</option>
                <?php foreach ($strands as $strand_ID => $strand_code): ?>
                    <option value="<?php echo htmlspecialchars($strand_ID); ?>">
                        <?php echo htmlspecialchars($strand_code); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Assign Strand</button>
            <button type="button" onclick="closeAssignStrandForm()">Cancel</button>
        </form>
    </div>

    <div id="assignGradeForm" class="form-container">
        <h2>Assign Grade to Teacher</h2>
        <form method="POST">
            <input type="hidden" name="action" value="assign_grade">
            <input type="hidden" id="assign_grade_teacherid" name="teacherid">
            <label for="grade">Choose Grade:</label>
            <select id="grade" name="grade" required>
                <option value="">-- Select Grade --</option>
                <option value="GRADE 11">GRADE 11</option>
                <option value="GRADE 12">GRADE 12</option>
            </select>
            <button type="submit">Assign Grade</button>
            <button type="button" onclick="closeAssignGradeForm()">Cancel</button>
        </form>
    </div>

    <div id="assignSubjectForm" class="form-container">
        <h2>Assign Subject to Teacher</h2>
        <form method="POST">
            <input type="hidden" name="action" value="assign_subject">
            <input type="hidden" id="assign_subject_teacherid" name="teacherid">
            <label for="subject">Choose Subject:</label>
            <select id="subject" name="subject" required>
                <option value="">-- Select Subject --</option>
                <!-- Options will be dynamically populated -->
            </select>
            <button type="submit">Assign Subject</button>
            <button type="button" onclick="closeAssignSubjectForm()">Cancel</button>
        </form>
    </div>

    <script>
        const sections = <?php echo json_encode($sections); ?>;
        const strands = <?php echo json_encode($strands); ?>;

        // Define toggleDropdown in the global scope
        function toggleDropdown(menu) {
            const dropdown = menu.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function showSectionForm(teacherid) {
            document.getElementById('teacherid').value = teacherid;
            const sectionDropdown = document.getElementById('secname');
            sectionDropdown.innerHTML = '<option value="">-- Select Section --</option>'; // Clear existing options

            // Fetch the teacher's strand_ID and grade
            const teacherCard = document.getElementById(`teacher-${teacherid}`);
            const strandText = teacherCard.querySelector('p:nth-child(3)').textContent.replace('Strand: ', '').trim();
            const gradeText = teacherCard.querySelector('p:nth-child(4)').textContent.replace('Grade: ', '').trim();

            // Map strand text to strand_ID
            const strand_ID = Object.keys(strands).find(key => strands[key] === strandText);

            // Filter sections based on strand_ID and grade
            const filteredSections = sections.filter(section => 
                section.strand_ID == strand_ID && section.shsgrade === gradeText
            );

            // Populate the dropdown with filtered sections
            filteredSections.forEach(section => {
                const option = document.createElement('option');
                option.value = section.section_ID;
                option.textContent = section.section_Name;
                sectionDropdown.appendChild(option);
            });

            // Show the form and blurred background
            document.getElementById('sectionForm').style.display = 'block';
            document.getElementById('blurredBackground').style.display = 'block';
        }

        function closeSectionForm() {
            // Hide the form and blurred background
            document.getElementById('sectionForm').style.display = 'none';
            document.getElementById('blurredBackground').style.display = 'none';
        }

        function showRemoveSectionForm(teacherid, sections) {
            const removeSecnameSelect = document.getElementById('remove_secname');
            const secteacherIDInput = document.getElementById('secteacher_ID');
            if (!removeSecnameSelect || !secteacherIDInput) {
                console.error('Required elements for removing a section are missing.');
                return;
            }

            removeSecnameSelect.innerHTML = ''; // Clear existing options
            sections.forEach(function (section) {
                const option = document.createElement('option');
                option.value = section.secteacher_ID; // Use secteacher_ID as the value
                option.textContent = section.section_Name;
                removeSecnameSelect.appendChild(option);
            });

            // Ensure the first option is selected by default and set the hidden input value
            if (sections.length > 0) {
                secteacherIDInput.value = sections[0].secteacher_ID; // Set the first section's ID by default
            }

            removeSecnameSelect.addEventListener('change', function () {
                secteacherIDInput.value = this.value; // Update secteacher_ID based on selection
            });

            // Show the form and blurred background
            document.getElementById('removeSectionForm').style.display = 'block';
            document.getElementById('blurredBackground').style.display = 'block';
        }

        function closeRemoveSectionForm() {
            // Hide the form and blurred background
            document.getElementById('removeSectionForm').style.display = 'none';
            document.getElementById('blurredBackground').style.display = 'none';
        }

        // Function to show the Assign Strand form
        function showAssignStrandForm(teacherid) {
            document.getElementById('assign_strand_teacherid').value = teacherid;
            document.getElementById('assignStrandForm').style.display = 'block';
            document.getElementById('blurredBackground').style.display = 'block';
        }

        function closeAssignStrandForm() {
            // Hide the form and blurred background
            document.getElementById('assignStrandForm').style.display = 'none';
            document.getElementById('blurredBackground').style.display = 'none';
        }

        // Function to show the Assign Grade form
        function showAssignGradeForm(teacherid) {
            document.getElementById('assign_grade_teacherid').value = teacherid;
            document.getElementById('assignGradeForm').style.display = 'block';
            document.getElementById('blurredBackground').style.display = 'block';
        }

        function closeAssignGradeForm() {
            // Hide the form and blurred background
            document.getElementById('assignGradeForm').style.display = 'none';
            document.getElementById('blurredBackground').style.display = 'none';
        }

        // Function to fetch subjects dynamically based on strand_ID and shsgrade
        function fetchSubjects(strandID, grade) {
            return fetch('fetch_subjects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ strand_ID: strandID, shsgrade: grade })
            })
            .then(response => response.json())
            .catch(error => {
                console.error('Error fetching subjects:', error);
                return [];
            });
        }

        // Function to show the Assign Subject form
        function showAssignSubjectForm(teacherid, strandID, grade) {
            document.getElementById('assign_subject_teacherid').value = teacherid;

            // Populate the subject dropdown dynamically using fetchSubjects
            const subjectDropdown = document.getElementById('subject');
            subjectDropdown.innerHTML = ''; // Clear existing options

            fetchSubjects(strandID, grade).then(subjects => {
                if (subjects.length > 0) {
                    subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.shssub_ID; // Use subject ID as value
                        option.textContent = subject.shssub_name; // Display subject name
                        subjectDropdown.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No subjects available';
                    subjectDropdown.appendChild(option);
                }
            });

            // Show the form and blurred background
            document.getElementById('assignSubjectForm').style.display = 'block';
            document.getElementById('blurredBackground').style.display = 'block';
        }

        function closeAssignSubjectForm() {
            // Hide the form and blurred background
            document.getElementById('assignSubjectForm').style.display = 'none';
            document.getElementById('blurredBackground').style.display = 'none';
        }

        // Function to confirm grade assignment
        function confirmAssignGrade() {
            const teacherid = document.getElementById('assign_grade_teacherid').value;
            const grade = document.getElementById('grade').value;

            if (teacherid && grade) {
                // Send a POST request to assign the grade
                const formData = new FormData();
                formData.append('action', 'assign_grade');
                formData.append('teacherid', teacherid);
                formData.append('grade', grade);

                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Show popup message
                    showPopupMessage(data.message, data.success ? 'success' : 'error');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showPopupMessage('Failed to assign grade.', 'error');
                });
            } else {
                showPopupMessage('Please select a grade.', 'error');
            }
        }

        // Function to confirm subject assignment
        function confirmAssignSubject() {
            const teacherid = document.getElementById('assign_subject_teacherid').value;
            const subject = document.getElementById('subject').value;

            if (teacherid && subject) {
                // Send a POST request to assign the subject
                const formData = new FormData();
                formData.append('action', 'assign_subject');
                formData.append('teacherid', teacherid);
                formData.append('subject', subject);

                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Show popup message
                    showPopupMessage(data.message, data.success ? 'success' : 'error');

                    // Update the teacher's card in real-time if successful
                    if (data.success) {
                        const teacherCard = document.getElementById(`teacher-${teacherid}`);
                        const fieldElement = teacherCard.querySelector('p:nth-child(2)'); // Assuming the field is the second <p>
                        fieldElement.textContent = `Field: ${data.teacherfield}`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showPopupMessage('Failed to assign subject.', 'error');
                });
            } else {
                showPopupMessage('Please select a subject.', 'error');
            }
        }

        // Function to show popup message
        function showPopupMessage(message, type) {
            const popup = document.createElement('div');
            popup.className = `popup-message ${type}`;
            popup.textContent = message;
            popup.style.position = 'fixed';
            popup.style.top = '20px';
            popup.style.right = '20px';
            popup.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
            popup.style.color = type === 'success' ? '#155724' : '#721c24';
            popup.style.padding = '10px';
            popup.style.borderRadius = '5px';
            popup.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            document.body.appendChild(popup);

            // Remove popup after 3 seconds
            setTimeout(() => {
                popup.remove();
            }, 3000);
        }

        function addTeacherRealTime(teacherData) {
            const teacherCard = createTeacherCard(teacherData);
            document.getElementById('teachersContainer').appendChild(teacherCard);
        }

        function createTeacherCard(teacher) {
            const card = document.createElement('div');
            card.className = 'card';
            card.id = `teacher-${teacher.teacherid}`;
            card.innerHTML = `
                <h3>${teacher.teachername} ${teacher.teachermidd} ${teacher.teacherlastname}</h3>
                <p>Field: ${teacher.teacherfield}</p>
                <p>Strand: ${teacher.strand_ID}</p>
                <p>Grade: ${teacher.grade}</p>
                <p>Assigned Sections: None</p>
                <div class="menu" onclick="toggleDropdown(this)">⋮</div>
                <div class="dropdown">
                    <a href="#" onclick="showSectionForm(${teacher.teacherid})">Assign Section</a>
                    <a href="#" onclick="showAssignStrandForm(${teacher.teacherid})">Assign Strand</a>
                    <a href="#" onclick="showAssignGradeForm(${teacher.teacherid})">Assign Grade</a>
                </div>
            `;
            return card;
        }

        // Example usage: Simulate adding a teacher in real-time
        document.querySelector('#addTeacherForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addTeacherRealTime(data.teacherCard);
                    showPopupMessage(data.message, 'success');
                } else {
                    showPopupMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showPopupMessage('Failed to add teacher.', 'error');
            });
        });

        async function refreshTeacherCards() {
            try {
                const response = await fetch('fetch_teachers.php'); // Backend script to fetch updated teacher data
                const teachers = await response.json();

                // Update the teacher cards dynamically
                teachers.forEach(teacher => {
                    const card = document.getElementById(`teacher-${teacher.teacherid}`);
                    if (card) {
                        card.querySelector('h3').textContent = `${teacher.teachername} ${teacher.teachermidd} ${teacher.teacherlastname}`;
                    }
                });
            } catch (error) {
                console.error('Error refreshing teacher cards:', error);
            }
        }

        // Periodically refresh teacher cards every 30 seconds
        setInterval(refreshTeacherCards, 1000);

        function searchTeachers() {
            const searchQuery = document.getElementById('teacherSearch').value.toLowerCase();
            const teacherCards = document.querySelectorAll('#teachersContainer .card');

            teacherCards.forEach(card => {
                const teacherName = card.querySelector('h3').textContent.toLowerCase();
                if (teacherName.includes(searchQuery)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function handleEnter(event) {
            if (event.key === 'Enter') {
                searchTeachers();
            }
        }

        function archiveTeacher(teacherid) {
            if (confirm('Are you sure you want to archive this teacher?')) {
                const formData = new FormData();
                formData.append('action', 'archive_teacher');
                formData.append('teacherid', teacherid);

                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the teacher card from the UI
                        const teacherCard = document.getElementById(`teacher-${teacherid}`);
                        if (teacherCard) {
                            teacherCard.remove();
                        }
                        showPopupMessage(data.message, 'success');
                    } else {
                        showPopupMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showPopupMessage('Failed to archive teacher.', 'error');
                });
            }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const burgerMenu = document.getElementById('burger-menu');
            const sideMenu = document.getElementById('side-menu');
            const closeMenu = document.getElementById('close-menu');

            function toggleMenu() {
                sideMenu.style.display = sideMenu.style.display === 'block' ? 'none' : 'block';
            }

            burgerMenu.addEventListener('click', toggleMenu);
            closeMenu.addEventListener('click', toggleMenu);
        });
    </script>
</body>
</html>