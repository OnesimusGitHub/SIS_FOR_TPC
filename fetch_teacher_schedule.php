<?php
header('Content-Type: application/json');

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the student is logged in
    if (!isset($_SESSION["student_id"]) || empty($_SESSION["student_id"])) {
        echo json_encode(['success' => false, 'message' => 'Student not logged in']);
        exit;
    }

    $shsstud_ID = $_SESSION["student_id"]; // Treat as VARCHAR

    // Fetch the section_ID for the logged-in student
    $stmt = $pdo->prepare("SELECT section_ID FROM tblshsstudent WHERE shsstud_ID = :shsstud_ID");
    $stmt->bindParam(':shsstud_ID', $shsstud_ID, PDO::PARAM_STR); // Bind as string
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || empty($student["section_ID"])) {
        echo json_encode(['success' => false, 'message' => 'Section not assigned to the student']);
        exit;
    }

    $section_ID = $student["section_ID"];

    // Get the current day of the week
    $currentDay = strtoupper(date('l')); // Convert to uppercase to match schedule_date format

    // Fetch the schedule of teachers assigned to the section for the current day
    $stmt = $pdo->prepare("
        SELECT t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield, 
               s.schedule_time, s.schedule_date, s.schedule_room
        FROM teachrinf t
        JOIN tblschedule s ON t.teacherid = s.teacher_ID
        WHERE s.section_ID = :section_ID AND s.schedule_date = :currentDay
    ");
    $stmt->bindParam(':section_ID', $section_ID, PDO::PARAM_INT);
    $stmt->bindParam(':currentDay', $currentDay, PDO::PARAM_STR);
    $stmt->execute();

    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($schedules)) {
        echo json_encode(['success' => false, 'message' => 'No schedules found for this section on ' . $currentDay]);
        exit;
    }

    echo json_encode(['success' => true, 'schedules' => $schedules]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
