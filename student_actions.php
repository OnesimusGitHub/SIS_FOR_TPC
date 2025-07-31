<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sis";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'assign_strand') {
            $studentid = $_POST['studentid'] ?? '';
            $strand = $_POST['strand'] ?? '';

            if (!empty($studentid) && !empty($strand)) {
                $stmt = $conn->prepare("UPDATE tblshsstudent SET strand_ID = ?, section_ID = NULL WHERE shsstud_ID = ?");
                $stmt->bind_param("ss", $strand, $studentid);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Strand updated successfully.";
                } else {
                    $response['message'] = "Failed to update strand.";
                }
                $stmt->close();
            } else {
                $response['message'] = "Invalid input.";
            }
        } elseif ($action === 'assign_grade') {
            $studentid = $_POST['studentid'] ?? '';
            $grade = $_POST['grade'] ?? '';

            if (!empty($studentid) && !empty($grade)) {
                $stmt = $conn->prepare("UPDATE tblshsstudent SET student_grade = ?, section_ID = NULL WHERE shsstud_ID = ?");
                $stmt->bind_param("ss", $grade, $studentid);

                if ($stmt->execute()) {
                    // Fetch updated student data
                    $result = $conn->query("SELECT shsstud_ID, student_grade, section_ID FROM tblshsstudent WHERE student_grade = '$grade'");
                    $updatedStudents = [];
                    while ($row = $result->fetch_assoc()) {
                        $updatedStudents[] = $row;
                    }

                    $response['success'] = true;
                    $response['message'] = "Grade assigned successfully!";
                    $response['updatedStudents'] = $updatedStudents;
                } else {
                    $response['message'] = "Failed to update grade.";
                }
                $stmt->close();
            } else {
                $response['message'] = "Invalid input.";
            }
        } elseif ($action === 'assign_section') {
            $studentid = $_POST['studentid'] ?? '';
            $section = $_POST['section'] ?? '';

            if (!empty($studentid) && !empty($section)) {
                $stmt = $conn->prepare("UPDATE tblshsstudent SET section_ID = ? WHERE shsstud_ID = ?");
                $stmt->bind_param("is", $section, $studentid);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Section assigned successfully!";
                } else {
                    $response['message'] = "Error assigning section.";
                }
                $stmt->close();
            } else {
                $response['message'] = "All fields are required for assigning a section.";
            }
        } elseif ($action === 'delete_student') {
            $studentId = $_POST['studentid'] ?? null;

            if (!$studentId) {
                $response['message'] = 'Missing student ID';
            } else {
                // Start a transaction
                $conn->begin_transaction();

                try {
                    // Delete the student from tblshslogin
                    $deleteLoginSql = "DELETE FROM tblshslogin WHERE shsstud_ID = ?";
                    $deleteLoginStmt = $conn->prepare($deleteLoginSql);
                    $deleteLoginStmt->bind_param('s', $studentId);
                    $deleteLoginStmt->execute();
                    $deleteLoginStmt->close();

                    // Delete the student from tblshsstudent
                    $deleteStudentSql = "DELETE FROM tblshsstudent WHERE shsstud_ID = ?";
                    $deleteStudentStmt = $conn->prepare($deleteStudentSql);
                    $deleteStudentStmt->bind_param('s', $studentId);
                    $deleteStudentStmt->execute();
                    $deleteStudentStmt->close();

                    // Commit the transaction
                    $conn->commit();

                    $response['success'] = true;
                    $response['message'] = 'Student deleted successfully';
                } catch (Exception $e) {
                    // Rollback the transaction on error
                    $conn->rollback();
                    $response['message'] = 'Failed to delete student';
                }
            }
        } elseif ($action === 'assign_school_year') {
            $studentId = $_POST['studentid'] ?? null;
            $schoolYear = $_POST['school_year'] ?? null;
            $schoolEnd = $_POST['school_end'] ?? null;

            if (!$studentId || !$schoolYear || !$schoolEnd) {
                $response['message'] = 'Missing required parameters.';
            } else {
                $sql = "UPDATE tblshsstudent SET school_year = ?, school_end = ? WHERE shsstud_ID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $schoolYear, $schoolEnd, $studentId);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'School year assigned successfully.';
                } else {
                    $response['message'] = 'Failed to assign school year.';
                }
                $stmt->close();
            }
        } else {
            $response['message'] = "Invalid action specified.";
        }
    } else {
        $response['message'] = "Invalid request method.";
    }

    echo json_encode($response); // Return JSON response directly
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>