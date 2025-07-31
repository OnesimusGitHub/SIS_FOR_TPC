<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate and fetch section_ID from GET parameters
    $section_ID = isset($_GET['section_ID']) ? (int)$_GET['section_ID'] : 0;

    if ($section_ID === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid section_ID']);
        exit;
    }

    // Fetch all teachers assigned to the section
    $stmt = $pdo->prepare("
        SELECT t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield
        FROM teachrinf t
        JOIN tblsecteacher st ON t.teacherid = st.teacher_ID
        WHERE st.section_ID = :section_ID
    ");
    $stmt->bindParam(':section_ID', $section_ID, PDO::PARAM_INT);
    $stmt->execute();

    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($teachers)) {
        echo json_encode(['success' => false, 'message' => 'No teachers found for this section']);
        exit;
    }

    echo json_encode(['success' => true, 'teachers' => $teachers]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
