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
    "absences" => [],
    "presents" => []
];

try {
    // Fetch all sections and their absences, excluding records with null datetest or totalstuds
    $sql = "SELECT sec.secname AS Section, sec.datetest AS date, 
                   COUNT(s.absent) AS total_absent, 
                   sec.totalstuds 
            FROM section sec
            LEFT JOIN sis.student s 
            ON sec.secname = s.Section AND sec.datetest = s.date AND s.absent > 0
            WHERE sec.datetest IS NOT NULL AND sec.totalstuds IS NOT NULL
            GROUP BY sec.secname, sec.datetest, sec.totalstuds";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $section = $row["Section"];
            $date = $row["date"];
            $totalAbsent = intval($row["total_absent"]);
            $totalStuds = intval($row["totalstuds"]);

            // Include sections with valid data
            if (!isset($data["absences"][$section])) {
                $data["absences"][$section] = [];
            }
            $data["absences"][$section][$date] = $totalAbsent;
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
