<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    header("Location: adminLogin.php");
    exit;
}

// Check if the user has the "Admin" role


$username = "root";
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Fetch strands and their sections
$strandsWithSections = [];
try {
    $sql = "SELECT sec.strand_ID, st.strand_code, sec.section_Name, sec.shsgrade 
            FROM tblshssection sec
            INNER JOIN tblstrand st ON sec.strand_ID = st.strand_ID
            ORDER BY st.strand_code, sec.shsgrade, sec.section_Name";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $strandCode = $row['strand_code'];
        $shsGrade = $row['shsgrade'];
        $sectionName = $row['section_Name'];

        if (!isset($strandsWithSections[$strandCode])) {
            $strandsWithSections[$strandCode] = [];
        }
        if (!isset($strandsWithSections[$strandCode][$shsGrade])) {
            $strandsWithSections[$strandCode][$shsGrade] = [];
        }
        $strandsWithSections[$strandCode][$shsGrade][] = $sectionName;
    }
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
    <title>View Sections by Strand</title>
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
            position: relative;
        }
        .back-dashboard-btn {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            padding: 5px 10px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        .back-dashboard-btn:hover {
            background-color: #003f7f;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-height: 80vh; /* Set maximum height for the container */
            overflow-y: auto; /* Enable vertical scrolling */
        }
        .strand-container {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .strand-title {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .grade-title {
            background-color: #f4f4f9;
            padding: 10px 15px;
            font-size: 1rem;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        .section-list {
            padding: 10px 15px;
            list-style: none;
            margin: 0;
            max-height: 300px; /* Set maximum height for the section list */
            overflow-y: auto; /* Enable vertical scrolling for the section list */
        }
        .section-list::-webkit-scrollbar {
            width: 8px; /* Width of the scrollbar */
        }
        .section-list::-webkit-scrollbar-thumb {
            background-color: #ccc; /* Color of the scrollbar thumb */
            border-radius: 4px; /* Rounded corners for the scrollbar thumb */
        }
        .section-list::-webkit-scrollbar-thumb:hover {
            background-color: #aaa; /* Hover color for the scrollbar thumb */
        }
        .section-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            transition: background-color 0.3s ease;
        }
        .section-list li:last-child {
            border-bottom: none;
        }
        .section-list li:hover {
            background-color: #f0f8ff;
        }
        .view-teachers-btn {
            margin-left: auto;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        .view-teachers-btn:hover {
            background-color: #0056b3;
        }
        .remove-section-btn {
            margin-left: 10px;
            padding: 5px 10px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        .remove-section-btn:hover {
            background-color: #c82333;
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
        .search-container {
            margin: 20px auto;
            max-width: 600px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .search-container input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <a href="admin_dashboard.php" class="back-dashboard-btn">Back to Dashboard</a>
        Sections Categorized by Strand
    </header>
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search by section name or strand...">
    </div>
    <div class="container" id="sectionsContainer">
        <?php foreach ($strandsWithSections as $strandCode => $grades): ?>
            <div class="strand-container" data-strand="<?php echo htmlspecialchars($strandCode); ?>">
                <div class="strand-title">Strand: <?php echo htmlspecialchars($strandCode); ?></div>
                <?php foreach ($grades as $grade => $sections): ?>
                    <div class="grade-title">Grade: <?php echo htmlspecialchars($grade); ?></div>
                    <ul class="section-list">
                        <?php foreach ($sections as $section): ?>
                            <li data-section="<?php echo htmlspecialchars($section); ?>">
                                <span><?php echo htmlspecialchars($section); ?></span>
                                <div>
                                    <a href="view_teachers.php?section_name=<?php echo urlencode($section); ?>" class="view-teachers-btn">View Teachers</a>
                                    <button class="remove-section-btn" onclick="showRemoveModal('<?php echo htmlspecialchars($section); ?>')">Remove Section</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <footer>&copy; <?php echo date("Y"); ?> Admin Dashboard</footer>

    <!-- Confirmation Modal -->
    <div id="removeModal" class="modal">
        <div class="modal-content">
            <h3>Are you sure you want to remove this section?</h3>
            <form id="removeSectionForm" method="GET" action="remove_section.php">
                <input type="hidden" name="section_name" id="sectionNameInput">
                <button type="submit" class="confirm-remove">Yes</button>
                <button type="button" class="cancel-remove" onclick="closeRemoveModal()">No</button>
            </form>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const sectionsContainer = document.getElementById('sectionsContainer');
        const strandContainers = sectionsContainer.querySelectorAll('.strand-container');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();

            strandContainers.forEach(strandContainer => {
                const strandCode = strandContainer.getAttribute('data-strand').toLowerCase();
                let hasVisibleSections = false;

                const sectionItems = strandContainer.querySelectorAll('li');
                sectionItems.forEach(sectionItem => {
                    const sectionName = sectionItem.getAttribute('data-section').toLowerCase();
                    if (strandCode.includes(query) || sectionName.includes(query)) {
                        sectionItem.style.display = 'flex';
                        hasVisibleSections = true;
                    } else {
                        sectionItem.style.display = 'none';
                    }
                });

                strandContainer.style.display = hasVisibleSections ? 'block' : 'none';
            });
        });

        function showRemoveModal(sectionName) {
            document.getElementById('sectionNameInput').value = sectionName;
            document.getElementById('removeModal').style.display = 'flex';
        }

        function closeRemoveModal() {
            document.getElementById('removeModal').style.display = 'none';
        }
    </script>
</body>
</html>
