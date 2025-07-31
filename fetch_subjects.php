<?php
header('Content-Type: application/json');

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $strand_ID = $data['strand_ID'] ?? null;
    $shsgrade = $data['shsgrade'] ?? null;
    $teacherid = $data['teacherid'] ?? null; // Added teacherid for updating teacherfield
    $subjectID = $data['subjectID'] ?? null; // Added subjectID for updating teacherfield

    if ($strand_ID && $shsgrade) {
        // Fetch subjects based on strand_ID and shsgrade
        $sql = "SELECT shssub_ID, shssub_name FROM tblshssubject WHERE strand_ID = :strand_ID AND shsgrade = :shsgrade";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':strand_ID', $strand_ID, PDO::PARAM_INT);
        $stmt->bindParam(':shsgrade', $shsgrade, PDO::PARAM_STR);
        $stmt->execute();

        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($subjects);
    } elseif ($teacherid && $subjectID) {
        // Fetch the subject name for updating teacherfield
        $sqlFetchSubject = "SELECT shssub_name FROM tblshssubject WHERE shssub_ID = :subjectID";
        $stmtFetchSubject = $pdo->prepare($sqlFetchSubject);
        $stmtFetchSubject->bindParam(':subjectID', $subjectID, PDO::PARAM_INT);
        $stmtFetchSubject->execute();
        $subjectName = $stmtFetchSubject->fetchColumn();

        if ($subjectName) {
            // Check if the subject is already assigned to the teacher
            $sqlCheck = "SELECT COUNT(*) FROM tblteachersubject WHERE teacherid = :teacherid AND subject = :subjectID";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
            $stmtCheck->bindParam(':subjectID', $subjectID, PDO::PARAM_INT);
            $stmtCheck->execute();
            $exists = $stmtCheck->fetchColumn();

            if (!$exists) {
                // Assign the subject to the teacher
                $sqlInsert = "INSERT INTO tblteachersubject (teacherid, subject) VALUES (:teacherid, :subjectID)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
                $stmtInsert->bindParam(':subjectID', $subjectID, PDO::PARAM_INT);
                $stmtInsert->execute();

                // Fetch the current teacherfield
                $sqlFetchTeacherField = "SELECT teacherfield FROM teachrinf WHERE teacherid = :teacherid";
                $stmtFetchTeacherField = $pdo->prepare($sqlFetchTeacherField);
                $stmtFetchTeacherField->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
                $stmtFetchTeacherField->execute();
                $currentTeacherField = $stmtFetchTeacherField->fetchColumn();

                // Append the new subject to the existing teacherfield
                $updatedTeacherField = $currentTeacherField ? $currentTeacherField . ", " . $subjectName : $subjectName;

                // Update the teacherfield in teachrinf
                $sqlUpdateField = "UPDATE teachrinf SET teacherfield = :teacherfield WHERE teacherid = :teacherid";
                $stmtUpdateField = $pdo->prepare($sqlUpdateField);
                $stmtUpdateField->bindParam(':teacherfield', $updatedTeacherField, PDO::PARAM_STR);
                $stmtUpdateField->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
                $stmtUpdateField->execute();

                echo json_encode(['success' => true, 'message' => 'Subject assigned and teacherfield updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Subject is already assigned to this teacher.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid subject ID.']);
        }
    } else {
        echo json_encode([]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
