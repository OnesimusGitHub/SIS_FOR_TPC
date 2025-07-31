<?php
require 'db_connection.php'; // Include your database connection

try {
    $sql = "SELECT 
                t.teacherid, 
                t.teachername, 
                t.teachermidd, 
                t.teacherlastname, 
                t.teacherfield, 
                t.strand_ID, 
                t.grade,
                s.section_Name
            FROM teachrinf t
            LEFT JOIN tblsecteacher st ON t.teacherid = st.teacher_ID
            LEFT JOIN tblshssection s ON st.section_ID = s.section_ID
            WHERE t.teachstat IS NULL -- Fetch only active teachers
            ORDER BY t.teachername ASC";
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new Exception('Database connection is not properly initialized.');
    }

    $teachers = [];
    foreach ($rows as $row) {
        $teacherId = $row['teacherid'];
        if (!isset($teachers[$teacherId])) {
            $teachers[$teacherId] = [
                'teacherid' => $row['teacherid'],
                'teachername' => $row['teachername'],
                'teachenormidd' => $row['teachermidd'],
                'teacherlastname' => $row['teacherlastname'],
                'teacherfield' => $row['teacherfield'],
                'strand_ID' => $row['strand_ID'],
                'grade' => $row['grade'],
                'sections' => []
            ];
        }
        if ($row['section_Name']) {
            $teachers[$teacherId]['sections'][] = ['section_Name' => $row['section_Name']];
        }
    }

    echo json_encode(array_values($teachers));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
