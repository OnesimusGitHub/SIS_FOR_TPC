<?php


session_start();

if (!isset($_SESSION["teacherid"]) || empty($_SESSION["teacherid"])) { // Check if teacherid is set and not empty
    error_log("Redirecting to teacherLogin.php: teacherid is not set or empty."); // Debugging log
    header("Location: teacherLogin.php");
    exit;
}

// Define database connection variables
$username = "root"; 
$password = "";
$database = "sis";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $sectionID = isset($_POST["section"]) ? trim($_POST["section"]) : ''; // Ensure 'section' is set
    $absent = 1; 
    $date = $_POST["date"];
    $teacherID = $_SESSION["teacherid"]; // Get the teacher ID from the session

    if (empty($sectionID)) {
        die("Error: Section ID is not specified. Please provide a valid section ID.");
    }

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch the section_Name using section_ID
        $sql = "SELECT section_Name FROM tblshssection WHERE section_ID = :sectionID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':sectionID', $sectionID);
        $stmt->execute();
        $sectionData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sectionData) {
            $error = "Invalid section.";
        } else {
            $sectionName = $sectionData['section_Name'];

            // Check if the teacher has already marked the student absent for this date
            $sql = "SELECT COUNT(*) FROM sis.student 
                    WHERE Name = :name AND Section = :sectionName AND date = :date AND teacher_ID = :teacherID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':sectionName', $sectionName);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':teacherID', $teacherID);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "You have already marked this student absent for this date.";
            } else {
                // Insert the absence record
                $sql = "INSERT INTO sis.student (Name, Section, absent, date, teacher_ID) 
                        VALUES (:name, :sectionName, :absent, :date, :teacherID)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':sectionName', $sectionName);
                $stmt->bindParam(':absent', $absent);
                $stmt->bindParam(':date', $date);
                $stmt->bindParam(':teacherID', $teacherID);

                if ($stmt->execute()) {
                    // Check if the section already has an entry for the given date
                    $sql = "SELECT COUNT(*) FROM section WHERE secname = :sectionName AND datetest = :datetest";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':sectionName', $sectionName);
                    $stmt->bindParam(':datetest', $date);
                    $stmt->execute();
                    $dateExists = $stmt->fetchColumn();

                    if ($dateExists > 0) {
                        // Update the total number of students for the section
                        $sql = "UPDATE section SET totalstuds = totalstuds - :absent WHERE secname = :sectionName AND datetest = :datetest";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':absent', $absent);
                        $stmt->bindParam(':sectionName', $sectionName);
                        $stmt->bindParam(':datetest', $date);
                        $stmt->execute();
                    } else {
                        // Insert a new record for the section
                        $sql = "SELECT COUNT(*) as noofstudents FROM tblshsstudent WHERE section_ID = :sectionID";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':sectionID', $sectionID);
                        $stmt->execute();
                        $sectionCountData = $stmt->fetch(PDO::FETCH_ASSOC);
                        $noofstudents = $sectionCountData['noofstudents'];

                        $totalstuds = $noofstudents - $absent;
                        $sql = "INSERT INTO section (secname, noofstudents, totalstuds, datetest) 
                                VALUES (:sectionName, :noofstudents, :totalstuds, :datetest)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':sectionName', $sectionName);
                        $stmt->bindParam(':noofstudents', $noofstudents);
                        $stmt->bindParam(':totalstuds', $totalstuds);
                        $stmt->bindParam(':datetest', $date);
                        $stmt->execute();
                    }

                    $success = "Record inserted successfully!";
                } else {
                    $error = "Error inserting record.";
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }

    unset($pdo);
}

// Ensure the 'section' parameter is set
$sectionID = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : (isset($_POST['section']) ? htmlspecialchars($_POST['section']) : '');

if (empty($sectionID)) {
    die("Error: Section ID is not specified. Please provide a valid section ID.");
}

$sectionName = '';
try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the section name using the section ID
    $sql = "SELECT section_Name FROM tblshssection WHERE section_ID = :sectionID";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':sectionID', $sectionID);
    $stmt->execute();
    $sectionData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sectionData) {
        $sectionName = $sectionData['section_Name'];
    } else {
        die("Error: Invalid section ID.");
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log($error); // Log the error for debugging
}

$studentsInSection = [];
try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch students assigned to the specific section
    $sql = "SELECT shsstud_ID, shstud_firstname, shstud_lastname 
            FROM tblshsstudent 
            WHERE section_ID = :sectionID";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':sectionID', $sectionID);
    $stmt->execute();
    $studentsInSection = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Log the query and results
    error_log("Query executed: $sql with section_ID = $sectionID");
    error_log("Students fetched: " . json_encode($studentsInSection));
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log($error); // Log the error for debugging
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Insert Record</title>
  <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f8f9fa;
    }
    .container {
        max-width: 800px;
        margin: 40px auto;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
        position: relative;
    }
    h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }
    .form-section {
        margin-bottom: 20px;
    }
    .form-section label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }
    .form-section input, .form-section button {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    .form-section button {
        background-color: #007bff;
        color: white;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .form-section button:hover {
        background-color: #0056b3;
    }
    .search-container {
        margin-bottom: 20px;
    }
    .search-container input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    .search-results {
        margin-top: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        max-height: 200px;
        overflow-y: auto;
        background-color: white;
    }
    .search-results div {
        padding: 10px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    .search-results div:hover {
        background-color: #f0f0f0;
    }
    .success-message {
        color: green;
        margin-bottom: 20px;
    }
    .error-message {
        color: red;
        margin-bottom: 20px;
    }
    .back-button {
        position: absolute;
        top: 20px;
        left: 20px;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .back-button:hover {
        background-color: #0056b3;
        transform: scale(1.05);
    }
  </style>
</head>
<body>
  <div class="container">
   <!-- Back Button -->
   <button class="back-button" onclick="window.location.href='teacher_dashboard.php'">Back</button>

    <h2>Insert New Record for Section: <?php echo htmlspecialchars($sectionName); ?></h2>
    <?php if (isset($success)): ?>
      <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
    <?php elseif (isset($error)): ?>
      <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <div class="form-section">
      <form action="" method="POST">
        <input type="hidden" name="section" value="<?php echo htmlspecialchars($sectionID); ?>">
        <label for="lrn">LRN:</label>
        <input type="text" id="lrn" name="lrn" placeholder="Select a student..." autocomplete="off" readonly>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required readonly>
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required>
        <button type="submit">Insert Record</button>
      </form>
    </div>
    <div class="search-container">
      <label for="search">Search Student:</label>
      <input type="text" id="search" placeholder="Search by student name or LRN..." autocomplete="off">
      <div class="search-results" id="searchResults">
        <?php if (!empty($studentsInSection)): ?>
          <?php foreach ($studentsInSection as $student): ?>
            <div class="student-card" onclick="selectStudent('<?php echo htmlspecialchars($student['shsstud_ID']); ?>', '<?php echo htmlspecialchars($student['shstud_firstname'] . ' ' . $student['shstud_lastname']); ?>')">
              <p><strong>LRN:</strong> <?php echo htmlspecialchars($student['shsstud_ID']); ?></p>
              <p><strong>Name:</strong> <?php echo htmlspecialchars($student['shstud_firstname'] . ' ' . $student['shstud_lastname']); ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No students are assigned to this section.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script>
    const students = <?php echo json_encode($studentsInSection); ?>;
    const searchInput = document.getElementById('search');
    const searchResults = document.getElementById('searchResults');
    const lrnInput = document.getElementById('lrn');
    const nameInput = document.getElementById('name');

    function selectStudent(lrn, name) {
      lrnInput.value = lrn;
      nameInput.value = name;
    }

    searchInput.addEventListener('input', function () {
      const query = searchInput.value.toLowerCase();
      searchResults.innerHTML = '';

      const filteredStudents = students.filter(student =>
        (student.shstud_firstname + ' ' + student.shstud_lastname).toLowerCase().includes(query) ||
        student.shsstud_ID.toLowerCase().includes(query)
      );

      filteredStudents.forEach(student => {
        const resultDiv = document.createElement('div');
        resultDiv.textContent = `${student.shstud_firstname} ${student.shstud_lastname} (LRN: ${student.shsstud_ID})`;
        resultDiv.addEventListener('click', function () {
          selectStudent(student.shsstud_ID, `${student.shstud_firstname} ${student.shstud_lastname}`);
        });
        searchResults.appendChild(resultDiv);
      });
    });
  </script>
</body>
</html>

