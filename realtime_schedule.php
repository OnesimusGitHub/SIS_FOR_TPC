<?php
session_start();

if (!isset($_SESSION["teacherid"])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized access."]);
    exit();
}

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Debugging: Log the teacher ID
    error_log("Teacher ID: " . $_SESSION["teacherid"]);

    // Get the current day name (e.g., MONDAY)
    $currentDay = strtoupper(date('l')); // Convert to uppercase to match the database values
    error_log("Current Day: " . $currentDay);

    // Fetch today's schedule for the logged-in teacher from the `tblschedule` table
    $sql = "SELECT s.schedule_date, s.schedule_time, s.schedule_room, sec.section_name
            FROM tblschedule s
            INNER JOIN tblshssection sec ON s.section_ID = sec.section_ID
            WHERE s.teacher_ID = :teacherid AND s.schedule_date = :currentDay";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacherid', $_SESSION["teacherid"]);
    $stmt->bindParam(':currentDay', $currentDay);

    // Execute the query and check for errors
    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo();
        error_log("Query failed: " . $errorInfo[2]); // Log the error to the server logs
        echo json_encode(["error" => "Query failed: " . $errorInfo[2]]);
        exit();
    }

    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Log the fetched schedule
    if (empty($schedule)) {
        error_log("No schedule found for teacher ID: " . $_SESSION["teacherid"]);
    } else {
        error_log("Fetched Schedule: " . json_encode($schedule));
    }

    echo json_encode($schedule);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Failed to fetch schedule: " . $e->getMessage()); // Log the exception message
    echo json_encode(["error" => "Failed to fetch schedule: " . $e->getMessage()]);
}
?>