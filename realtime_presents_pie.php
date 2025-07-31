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

    // Count total presents categorized by daily, weekly, and monthly
    $sql = "SELECT datetest AS date, SUM(totalstuds) AS total_present
            FROM section
            WHERE datetest IS NOT NULL AND totalstuds IS NOT NULL
            GROUP BY datetest";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $date = $row["date"];
            $totalPresent = intval($row["total_present"]);

            if ($date === $today) {
                $data["daily"] += $totalPresent;
            }
            if ($date >= $weekAgo) {
                $data["weekly"] += $totalPresent;
            }
            if ($date >= $monthAgo) {
                $data["monthly"] += $totalPresent;
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
