<?php
header('Content-Type: application/json');

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "ERROR: Could not connect. " . $e->getMessage()]);
    exit;
}

$response = ['success' => false];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacherid = trim($_POST["teacherid"] ?? '');
    $grade = trim($_POST["grade"] ?? '');

    if ($teacherid && $grade) {
        try {
            $sql = "UPDATE teachrinf SET grade = :grade WHERE teacherid = :teacherid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':grade', $grade);
            $stmt->bindParam(':teacherid', $teacherid);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Grade assigned successfully!";
            } else {
                $response['message'] = "Error assigning grade.";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    } else {
        $response['message'] = "Invalid input.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

unset($pdo);
echo json_encode($response);
