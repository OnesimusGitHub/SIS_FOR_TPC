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
    "presents" => []
];

try {
    // Fetch all sections and their total students, excluding records with null datetest or totalstuds
    $sql = "SELECT sec.secname, sec.datetest, sec.totalstuds AS total_present
            FROM section sec
            WHERE sec.datetest IS NOT NULL AND sec.totalstuds IS NOT NULL";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $section = $row["secname"];
            $date = $row["datetest"];
            $totalPresent = intval($row["total_present"]);

            // Include sections with valid data
            if (!isset($data["presents"][$section])) {
                $data["presents"][$section] = [];
            }
            $data["presents"][$section][$date] = $totalPresent;
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
