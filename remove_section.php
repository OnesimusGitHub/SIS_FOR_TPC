<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    header("Location: adminLogin.php");
    exit;
}

if (!isset($_GET['section_name']) || empty($_GET['section_name'])) {
    die("Invalid section name.");
}

$sectionName = $_GET['section_name'];

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the section ID based on the section name
    $sectionIdSql = "SELECT section_ID FROM tblshssection WHERE section_Name = :section_name";
    $sectionIdStmt = $pdo->prepare($sectionIdSql);
    $sectionIdStmt->bindParam(':section_name', $sectionName, PDO::PARAM_STR);
    $sectionIdStmt->execute();
    $sectionId = $sectionIdStmt->fetchColumn();

    if (!$sectionId) {
        die("Section not found.");
    }

    // Remove the section assignment from students
    $updateStudentsSql = "UPDATE tblshsstudent SET section_ID = NULL WHERE section_ID = :section_id";
    $updateStudentsStmt = $pdo->prepare($updateStudentsSql);
    $updateStudentsStmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);
    $updateStudentsStmt->execute();

    // Delete section assignments from tblsecteacher
    $deleteAssignmentsSql = "DELETE FROM tblsecteacher WHERE section_ID = :section_id";
    $deleteAssignmentsStmt = $pdo->prepare($deleteAssignmentsSql);
    $deleteAssignmentsStmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);
    $deleteAssignmentsStmt->execute();

    // Delete the section from the database
    $deleteSql = "DELETE FROM tblshssection WHERE section_ID = :section_id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);

    if ($deleteStmt->execute()) {
        header("Location: view_sections_by_strand.php?message=" . urlencode("Section and its assignments removed successfully."));
        exit;
    } else {
        die("Failed to remove section.");
    }
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

unset($pdo);
?>
