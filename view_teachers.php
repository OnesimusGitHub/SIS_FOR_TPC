<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    header("Location: adminLogin.php");
    exit;
}

if (!isset($_GET['section_name']) || empty($_GET['section_name'])) {
    die("Invalid section name.");
}

$sectionName = $_GET['section_name'];

$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Fetch teachers assigned to the section
$teachers = [];
try {
    $sql = "SELECT t.teacherid, t.teachername, t.teachermidd, t.teacherlastname, t.teacherfield 
            FROM tblsecteacher st
            INNER JOIN teachrinf t ON st.teacher_ID = t.teacherid
            INNER JOIN tblshssection s ON st.section_ID = s.section_ID
            WHERE s.section_Name = :section_name";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':section_name', $sectionName, PDO::PARAM_STR);
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not execute query. " . $e->getMessage());
}

unset($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Teachers for <?php echo htmlspecialchars($sectionName); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .teacher-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .teacher-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            transition: background-color 0.3s ease;
        }
        .teacher-list li:last-child {
            border-bottom: none;
        }
        .teacher-list li:hover {
            background-color: #f0f8ff;
        }
        .remove-btn {
            padding: 5px 10px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        .remove-btn:hover {
            background-color: #c82333;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .back-btn:hover {
            background-color: #0056b3;
        }
        footer {
            text-align: center;
            padding: 10px;
            background-color: #007bff;
            color: white;
            margin-top: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }
        .modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }
        .modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        .modal-content .confirm-remove {
            background-color: #dc3545;
            color: white;
        }
        .modal-content .confirm-remove:hover {
            background-color: #c82333;
        }
        .modal-content .cancel-remove {
            background-color: #f2f2f2;
            color: #333;
        }
        .modal-content .cancel-remove:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <header>Teachers Assigned to Section: <?php echo htmlspecialchars($sectionName); ?></header>
    <div class="container">
        <?php if (!empty($teachers)): ?>
            <ul class="teacher-list">
                <?php foreach ($teachers as $teacher): ?>
                    <li>
                        <span>
                            <?php echo htmlspecialchars($teacher['teachername'] . ' ' . $teacher['teachermidd'] . ' ' . $teacher['teacherlastname']); ?>
                            <br>
                            <small>Field: <?php echo htmlspecialchars($teacher['teacherfield']); ?></small>
                        </span>
                        <button class="remove-btn" onclick="showRemoveModal('<?php echo htmlspecialchars($teacher['teacherid']); ?>', '<?php echo htmlspecialchars($sectionName); ?>')">Remove from Section</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No teachers assigned to this section.</p>
        <?php endif; ?>
        <a href="view_sections_by_strand.php" class="back-btn">Back to Sections</a>
    </div>
    <footer>&copy; <?php echo date("Y"); ?> Admin Dashboard</footer>

    <!-- Confirmation Modal -->
    <div id="removeModal" class="modal">
        <div class="modal-content">
            <h3>Are you sure you want to remove this teacher from the section?</h3>
            <form id="removeTeacherForm" method="GET" action="remove_teacher_from_section.php">
                <input type="hidden" name="teacher_id" id="teacherIdInput">
                <input type="hidden" name="section_name" id="sectionNameInput">
                <button type="submit" class="confirm-remove">Yes</button>
                <button type="button" class="cancel-remove" onclick="closeRemoveModal()">No</button>
            </form>
        </div>
    </div>

    <script>
        function showRemoveModal(teacherId, sectionName) {
            document.getElementById('teacherIdInput').value = teacherId;
            document.getElementById('sectionNameInput').value = sectionName;
            document.getElementById('removeModal').style.display = 'flex';
        }

        function closeRemoveModal() {
            document.getElementById('removeModal').style.display = 'none';
        }
    </script>
</body>
</html>
