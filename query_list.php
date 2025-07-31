<?php
// This file contains a list of all SQL queries (INSERT, UPDATE, SELECT, DELETE) used in the repository.

// INSERT Queries
$insertQueries = [
    "INSERT INTO tblshssection (strand_ID, section_name, shsgrade) VALUES (:strand_ID, :section_name, :shsgrade)",
    "INSERT INTO tblsecteacher (teacher_ID, section_ID) VALUES (:teacherid, :section_ID)",
    "INSERT INTO teachrinf (teachername, teachermidd, teacherlastname, teacherfield, strand_ID, grade) 
        VALUES (:teachername, :teachermidd, :teacherlastname, :teacherfield, :strand_ID, :grade)",
    "INSERT INTO tbladmin (admin_email, admin_contactno, admin_fnam, admin_mname, admin_lname, admin_exten, admin_sex, admin_dob, admin_age, admin_caddress, admin_paddress, admin_pfp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    "INSERT INTO tbladminlogin (adminlogin_email, adminlogin_password, admin_ID) VALUES (?, ?, ?)",
    "INSERT INTO tblregistrar (registrar_email, registrar_contactno, registrar_fname, registrar_mname, registrar_lname, registrar_exten, registrar_sex, registrar_dob, registrar_age,
         registrar_caddress, registrar_paddress, registrar_pfp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    "INSERT INTO tblregistrarlogin (registrarlogin_email, registrarlogin_password, registrar_ID) VALUES (?, ?, ?)",
    "INSERT INTO tblshsstudent (shsstud_ID, shstud_email, shstud_contactno, shstud_firstname, shstud_middlename, shstud_lastname, shstud_extensions, shstud_sex, shstud_dob, shstud_age,
         shstud_cadd, shstud_padd, shstud_pfp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    "INSERT INTO tblshslogin (shslogin_email, shslogin_password, shsstud_ID) VALUES (?, ?, ?)"
];

// UPDATE Queries
$updateQueries = [
    "UPDATE teachrinf SET strand_ID = :strand_ID WHERE teacherid = :teacherid",
    "UPDATE teachrinf SET grade = :grade WHERE teacherid = :teacherid",
    "UPDATE teachrinf SET teacherfield = :teacherfield WHERE teacherid = :teacherid",
    "UPDATE teachrinf SET teachstat = 'ARCHIVED' WHERE teacherid = :teacherid",
    "UPDATE tblshsstudent SET studstat = 'ARCHIVED' WHERE school_end < :currentDate AND (studstat IS NULL OR studstat != 'ARCHIVED')",
    "UPDATE tblshsstudent 
    SET studstat = 'ARCHIVED' 
    WHERE school_end < '$currentDate' AND (studstat IS NULL OR studstat != 'ARCHIVED')",
    "UPDATE tblshssection SET section_name = :section_name WHERE section_ID = :section_ID",
    "UPDATE tblshssection SET strand_ID = :strand_ID WHERE section_ID = :section_ID",
    "UPDATE tblshssection SET shsgrade = :shsgrade WHERE section_ID = :section_ID",
    "UPDATE tblsecteacher SET teacher_ID = :teacherid WHERE secteacher_ID = :secteacher_ID",
    "UPDATE tblsecteacher SET section_ID = :section_ID WHERE secteacher_ID = :secteacher_ID",
    
];


// SELECT Queries
$selectQueries = [
    "SELECT strand_ID, strand_code FROM tblstrand",
    "SELECT COUNT(*) FROM tblshssection WHERE section_name = :section_name",
    "SELECT t.teacherid, t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield, t.strand_ID, t.grade 
        FROM teachrinf t WHERE t.teachstat IS NULL ORDER BY t.teachername ASC",
    "SELECT DISTINCT st.secteacher_ID, s.section_Name 
        FROM tblsecteacher st INNER JOIN tblshssection s ON st.section_ID = s.section_ID WHERE st.teacher_ID = :teacherid",
    "SELECT section_ID, section_Name, strand_ID, shsgrade FROM tblshssection",
    "SELECT shssub_name FROM tblshssubject WHERE shssub_ID = :subject",
    "SELECT teacherid FROM login WHERE loginid = :loginid",
    "SELECT sec.section_ID, sec.section_name, 
        (SELECT COUNT(*) FROM tblshsstudent st WHERE st.section_ID = sec.section_ID) AS no_of_students
        FROM tblsecteacher st INNER JOIN tblshssection sec ON st.section_ID = sec.section_ID
        WHERE st.teacher_ID = :teacherid GROUP BY sec.section_ID, sec.section_name",
        "SELECT sec.strand_ID, st.strand_code, sec.section_Name, sec.shsgrade 
        FROM tblshssection sec
        INNER JOIN tblstrand st ON sec.strand_ID = st.strand_ID
        ORDER BY st.strand_code, sec.shsgrade, sec.section_Name"
];

// DELETE Queries
$deleteQueries = [
    "DELETE FROM tblsecteacher WHERE secteacher_ID = :secteacher_ID",
    "DELETE FROM tblshsstudent WHERE shsstud_ID = :shsstud_ID",
    "DELETE FROM tbladmin WHERE admin_ID = :admin_ID",
    "DELETE FROM tblregistrar WHERE registrar_ID = :registrar_ID",
    "DELETE FROM tblshslogin WHERE shslogin_ID = :shslogin_ID",
    "DELETE FROM tbladminlogin WHERE adminlogin_ID = :adminlogin_ID",
    "DELETE FROM tblregistrarlogin WHERE registrarlogin_ID = :registrarlogin_ID"
];

// Output the queries
echo "<h1>List of INSERT Queries</h1>";
echo "<ul>";
foreach ($insertQueries as $query) {
    echo "<li>" . htmlspecialchars($query) . "</li>";
}
echo "</ul>";

echo "<h1>List of UPDATE Queries</h1>";
echo "<ul>";
foreach ($updateQueries as $query) {
    echo "<li>" . htmlspecialchars($query) . "</li>";
}
echo "</ul>";

echo "<h1>List of SELECT Queries</h1>";
echo "<ul>";
foreach ($selectQueries as $query) {
    echo "<li>" . htmlspecialchars($query) . "</li>";
}
echo "</ul>";

echo "<h1>List of DELETE Queries</h1>";
echo "<ul>";
foreach ($deleteQueries as $query) {
    echo "<li>" . htmlspecialchars($query) . "</li>";
}
echo "</ul>";
?>
