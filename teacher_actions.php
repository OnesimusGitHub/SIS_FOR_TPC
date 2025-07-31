<?php
header('Content-Type: application/json');

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => "ERROR: Could not connect. " . $e->getMessage()]);
    exit;
}

$response = ['success' => false];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign_section') {
        $secname = trim($_POST["secname"] ?? '');
        $teacherid = trim($_POST["teacherid"] ?? '');

        try {
            $sql = "INSERT INTO section (secname, teacherid) VALUES (:secname, :teacherid)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':secname', $secname);
            $stmt->bindParam(':teacherid', $teacherid);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Section assigned successfully!";
            } else {
                $response['message'] = "Error assigning section.";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'remove_section') {
        $secname = trim($_POST["secname"] ?? '');
        $teacherid = trim($_POST["teacherid"] ?? '');

        try {
            $sql = "UPDATE section SET teacherid = NULL WHERE secname = :secname AND teacherid = :teacherid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':secname', $secname);
            $stmt->bindParam(':teacherid', $teacherid);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Section removed successfully!";
            } else {
                $response['message'] = "Error removing section.";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'assign_strand') {
        $teacherid = trim($_POST["teacherid"] ?? '');
        $strand_ID = trim($_POST["strand_ID"] ?? '');

        try {
            $sql = "UPDATE teachrinf SET strand_ID = :strand_ID WHERE teacherid = :teacherid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':strand_ID', $strand_ID);
            $stmt->bindParam(':teacherid', $teacherid);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Strand assigned successfully!";
            } else {
                $response['message'] = "Error assigning strand.";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'assign_subject') {
        $teacherid = trim($_POST["teacherid"] ?? '');
        $subject = trim($_POST["subject"] ?? ''); // Corrected to use 'subject'

        try {
            // Ensure only one subject is assigned to the teacher
            $sql = "UPDATE teachrinf SET teacherfield = :subject WHERE teacherid = :teacherid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':teacherid', $teacherid);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Subject assigned successfully!";
            } else {
                $response['message'] = "Error assigning subject.";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'assign_grade') {
        $teacherid = trim($_POST["teacherid"] ?? '');
        $grade = trim($_POST["grade"] ?? '');

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
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        // Fetch teachers and their sections
        $teachers = [];
        $sql = "SELECT t.teacherid, t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield, t.strand_ID, t.grade, s.secname 
                FROM teachrinf t 
                LEFT JOIN section s ON t.teacherid = s.teacherid";
        $result = $pdo->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $teacherid = $row['teacherid'];
            if (!isset($teachers[$teacherid])) {
                $teachers[$teacherid] = [
                    'teacherid' => $teacherid,
                    'teachername' => $row['teachername'],
                    'teachermidd' => $row['teachermidd'],
                    'teacherlastname' => $row['teacherlastname'],
                    'teacherfield' => $row['teacherfield'],
                    'strand_ID' => $row['strand_ID'],
                    'grade' => $row['grade'], // Include grade in the response
                    'sections' => []
                ];
            }
            if ($row['secname'] && !in_array($row['secname'], $teachers[$teacherid]['sections'])) {
                $teachers[$teacherid]['sections'][] = $row['secname'];
            }
        }

        // Fetch strands
        $strands = [];
        $sql = "SELECT strand_ID, strand_code FROM tblstrand";
        $result = $pdo->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $strands[$row['strand_ID']] = $row['strand_code'];
        }

        // Fetch sections
        $sections = [];
        $sql = "SELECT DISTINCT secname FROM section";
        $result = $pdo->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $sections[] = $row['secname'];
        }

        $response['success'] = true;
        $response['teachers'] = $teachers;
        $response['strands'] = $strands;
        $response['sections'] = $sections;
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
}

unset($pdo);
echo json_encode($response);
