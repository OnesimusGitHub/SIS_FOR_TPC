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

$data = [
    "daily" => 0,
    "weekly" => 0,
    "monthly" => 0
];

try {
    $today = date('Y-m-d');
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    $monthAgo = date('Y-m-d', strtotime('-1 month'));

    // Count total absences categorized by daily, weekly, and monthly
    $sql = "SELECT date, COUNT(*) AS total_absent
            FROM sis.student
            WHERE absent > 0 AND date IS NOT NULL
            GROUP BY date";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $date = $row["date"];
            $totalAbsent = intval($row["total_absent"]);

            if ($date === $today) {
                $data["daily"] += $totalAbsent;
            }
            if ($date >= $weekAgo) {
                $data["weekly"] += $totalAbsent;
            }
            if ($date >= $monthAgo) {
                $data["monthly"] += $totalAbsent;
            }
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
