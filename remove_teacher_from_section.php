<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    header("Location: adminLogin.php");
    exit;
}

if (!isset($_GET['teacher_id']) || !isset($_GET['section_name'])) {
    die("Invalid request.");
}

$teacherId = $_GET['teacher_id'];
$sectionName = $_GET['section_name'];

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the section ID based on the section name
    $sectionSql = "SELECT section_ID FROM tblshssection WHERE section_Name = :section_name";
    $sectionStmt = $pdo->prepare($sectionSql);
    $sectionStmt->bindParam(':section_name', $sectionName, PDO::PARAM_STR);
    $sectionStmt->execute();
    $sectionId = $sectionStmt->fetchColumn();

    if (!$sectionId) {
        die("Section not found.");
    }

    // Delete the teacher-section assignment
    $deleteSql = "DELETE FROM tblsecteacher WHERE teacher_ID = :teacher_id AND section_ID = :section_id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $deleteStmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);

    if ($deleteStmt->execute()) {
        header("Location: view_teachers.php?section_name=" . urlencode($sectionName) . "&message=" . urlencode("Teacher removed successfully."));
        exit;
    } else {
        die("Failed to remove teacher from section.");
    }
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

unset($pdo);
?>
