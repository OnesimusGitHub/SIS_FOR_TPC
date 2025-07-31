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
    // Fetch absences
    $sql = "SELECT s.Section, s.date, COUNT(s.absent) AS total_absent
            FROM sis.student s
            WHERE s.absent > 0
            GROUP BY s.Section, s.date";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $section = $row["Section"];
            $date = $row["date"];
            if (!isset($data["absences"][$section])) {
                $data["absences"][$section] = [];
            }
            $data["absences"][$section][$date] = intval($row["total_absent"]);
        }
        unset($result);
    }

    // Fetch presents
    $sql = "SELECT sec.secname, sec.datetest, sec.totalstuds AS total_present
            FROM section sec";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $section = $row["secname"];
            $date = $row["datetest"];
            if (!isset($data["presents"][$section])) {
                $data["presents"][$section] = [];
            }
            $data["presents"][$section][$date] = intval($row["total_present"]);
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
