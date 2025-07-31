<?php
$username = "root"; 
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

$data = [];

try {
    // Fetch total students grouped by datetest, excluding records with null datetest or totalstuds
    $sql = "SELECT datetest AS date, SUM(totalstuds) AS total_students
            FROM section
            WHERE datetest IS NOT NULL AND totalstuds IS NOT NULL
            GROUP BY datetest";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $date = $row["date"];
            $totalStudents = intval($row["total_students"]);

            // Add the total students for the date
            $data[$date] = $totalStudents;
        }
        unset($result);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

unset($pdo);

header('Content-Type: application/json');
echo json_encode($data);
