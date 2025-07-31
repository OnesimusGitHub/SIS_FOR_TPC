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
    "sections" => [],
    "teachers" => []
];

try {
    // Fetch sections
    $sql = "SELECT DISTINCT secname FROM section";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data["sections"][] = $row['secname'];
        }
        unset($result);
    }

    // Fetch teachers and their sections
    $sql = "SELECT t.teacherid, t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield, s.secname 
            FROM teachrinf t 
            LEFT JOIN section s ON t.teacherid = s.teacherid";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $teacherid = $row['teacherid'];
            if (!isset($data["teachers"][$teacherid])) {
                $data["teachers"][$teacherid] = [
                    "teacherid" => $row['teacherid'],
                    "teachername" => $row['teachername'],
                    "teachermidd" => $row['teachermidd'],
                    "teacherlastname" => $row['teacherlastname'],
                    "teacherfield" => $row['teacherfield'],
                    "sections" => []
                ];
            }
            if ($row['secname'] && !in_array($row['secname'], $data["teachers"][$teacherid]["sections"])) {
                $data["teachers"][$teacherid]["sections"][] = $row['secname'];
            }
        }
        unset($result);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

unset($pdo);

header('Content-Type: application/json');
echo json_encode($data);
