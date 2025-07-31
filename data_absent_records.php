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

$sections = []; 

try {
    $sql = "SELECT Name, Section, absent, date FROM sis.student WHERE absent > 0 ORDER BY date";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $section = $row["Section"];
            $monthYear = date("F Y", strtotime($row["date"]));
            if (!isset($sections[$section])) {
                $sections[$section] = [];
            }
            if (!isset($sections[$section][$monthYear])) {
                $sections[$section][$monthYear] = [];
            }
            $sections[$section][$monthYear][] = [
                "Name" => $row["Name"],
                "Section" => $row["Section"],
                "absent" => $row["absent"],
                "date" => $row["date"]
            ];
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
echo json_encode($sections);
?>
