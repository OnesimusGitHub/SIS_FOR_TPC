<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Database connection
$database = "sis";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check for archived filter
    $archivedFilter = isset($_GET['archived']) && $_GET['archived'] === 'null' ? 'IS NULL' : '= 1';

    // Fetch grades based on the archived filter
    $sql = "SELECT g.shsstud_ID, g.first_grading, g.second_grading, g.third_grading, g.fourth_grading, 
                   g.teacher_name, g.teacher_field, s.shstud_firstname, s.shstud_lastname, sec.section_name, 
                   t.grade
            FROM tblgrades g
            INNER JOIN tblshsstudent s ON g.shsstud_ID = s.shsstud_ID
            INNER JOIN teachrinf t ON g.teacher_name = t.teachername
            INNER JOIN tblshssection sec ON s.section_ID = sec.section_ID
            WHERE g.archived $archivedFilter
            ORDER BY s.shstud_lastname ASC";
    $stmt = $pdo->query($sql);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear output buffer to prevent unintended output
    if (ob_get_length()) {
        ob_clean();
    }

    // Set content type to JSON and return grades
    header("Content-Type: application/json");
    echo json_encode($grades, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
} catch (PDOException $e) {
    // Clear output buffer to prevent unintended output
    if (ob_get_length()) {
        ob_clean();
    }

    header("Content-Type: application/json");
    echo json_encode(['error' => 'ERROR: Could not connect. ' . htmlspecialchars($e->getMessage())]);
}

unset($pdo);
?>
