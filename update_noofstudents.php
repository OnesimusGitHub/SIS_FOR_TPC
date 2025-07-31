<?php
session_start();

if (!isset($_SESSION["loginid"])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$username = "root"; 
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["section"]) && isset($_POST["noofstudents"])) {
        $section = trim($_POST["section"]);
        $noofstudents = intval($_POST["noofstudents"]);

        $sql = "UPDATE section SET noofstudents = :noofstudents WHERE secname = :section";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':noofstudents', $noofstudents, PDO::PARAM_INT);
        $stmt->bindParam(':section', $section, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(["success" => "Number of students updated successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update number of students."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid request."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

unset($pdo);
