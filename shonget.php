<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"]) || empty($_SESSION["admin_id"])) {
    // Prevent redirection loop by ensuring the current page is not adminLogin.php
    if (basename($_SERVER['PHP_SELF']) !== 'adminLogin.php') {
        header("Location: adminLogin.php"); // Redirect to admin login page
        exit;
    }
}

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: adminLogin.php");
    exit;
}

$username = "root"; 
$password = "";
$database = "sis";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

$sections = []; 

try {
    $sql = "SELECT s.Section, s.date, COUNT(s.Name) AS total_absent, sec.totalstuds 
            FROM sis.student s
            JOIN section sec ON s.Section = sec.secname AND s.date = sec.datetest
            WHERE s.absent > 0
            GROUP BY s.Section, s.date, sec.totalstuds";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $section = $row["Section"];
            $date = $row["date"];
            if (!isset($sections[$section])) {
                $sections[$section] = [];
            }
            $sections[$section][$date] = [
                "total_absent" => $row["total_absent"],
                "totalstuds" => $row["totalstuds"]
            ];
        }
        unset($result);
    } else {
        echo "No records matching your query were found.";
    }
} catch(PDOException $e) {
    die("ERROR: Could not execute $sql. " . $e->getMessage());
}

unset($pdo);

// Preprocess sections to filter out invalid ones
$filteredSections = [];
foreach ($sections as $section => $dates) {
    $hasValidData = false;
    foreach ($dates as $date => $stats) {
        if (!empty($stats["total_absent"]) || (!empty($stats["totalstuds"]) && $stats["totalstuds"] > 0)) {
            $hasValidData = true;
            break;
        }
    }
    if ($hasValidData) {
        $filteredSections[$section] = $dates;
    }
}

?>
    
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
  <title>ANALYTICS PO</title>
  <style>
    .chartBox {
      position: relative;
      width: 500px;
      height: 400px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .sectionContainer {
      width: 100%; /* Default width */
      max-width: 800px; /* Maximum width */

      padding: 20px; /* Add padding */
      box-sizing: border-box; /* Include padding in width/height */
    }
    .pieChartContainer {
      width: 200px; /* Adjust this to resize the pie chart */
      height: 200px; /* Adjust this to resize the pie chart */
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: -320px;
      margin-left: 490px;
    }
    .pieChartContainer canvas {
      width: 100%; /* Inherit size from parent div */
      height: 100%; /* Inherit size from parent div */
    }
    .lineGraphSummaryContainer {
      position: relative;
      width: 100%;
      max-width: 800px;
      margin: 20px ;
      background: #f9f9f9;
      border: 1px solid #ccc;
      padding: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .lineGraphSummaryContainer canvas {
      display: block;
      max-width: 100%;
      height: auto;
    }
    .navButton {
      margin: 20px 0;
    }
    .chartContainer {
      display: flex;
      justify-content: center; /* Center the entire container */
      align-items: center;
      margin-bottom: 40px;
      border: 1px solid #ccc; /* Add border for the box */
      padding: 20px; /* Add padding inside the box */
      border-radius: 8px; /* Add rounded corners */
      background-color: #f9f9f9; /* Add background color for the box */
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add shadow for the box */
      width: 80%; /* Center the container with a fixed width */
      margin-left: auto; /* Center the container */
      margin-right: auto;
    }
    .lineGraphContainer {
      flex: 1; /* Ensure "Total Absences" takes one side */
      margin-right: 20px; /* Add spacing between the sections */
      text-align: center; /* Center the content inside */
    }
    .pieChartContainer {
      flex: 1; /* Ensure "Total Presents" takes the other side */
      text-align: center; /* Center the content inside */
    }
    body {
      display: flex;
      flex-direction: column;
      align-items: center; /* Center all content horizontally */
      justify-content: center; /* Center all content vertically */
      min-height: 100vh; /* Ensure the body takes the full height of the viewport */
      margin: 0;
    }
    .navButton, button {
      margin: 20px 0;
      text-align: center;
    }
    #chartsContainer {
      width: 40%;
      margin-right: 925px;
      border-radius: 15px;
      display: flex;
      flex-direction: column;
      align-items: center; /* Center the charts container */
      overflow-y: auto; /* Add vertical scroll bar */
      max-height: 375px; /* Limit the height of the container */
      padding: 10px; /* Add padding for better spacing */
      border: 1px solid #ccc; /* Add border for better visibility */
      background-color: #f9f9f9; /* Add background color */
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add shadow for better separation */
    }
    body {
      margin-top: -30px;
      font-family: Arial, sans-serif;
      max-width: 100%; /* Ensure the body takes up the full width */
      /* Reduced padding to minimize white space */
      background-color: #f4f4f4; /* Add background color for the page */
    }
    .header1 {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      max-width: 100%; /* Ensure the header spans the full width */
      margin: 0 auto; /* Center the header */
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 5px 20px; /* Reduced padding for less white space */
      background: linear-gradient(to top, #0630C2, #FFFFFF); /* Correct gradient colors */
      border-bottom: 1px solid #ddd;
      z-index: 1000; /* Ensures the header stays above other elements */
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow for better separation */
    }
    .d {
      opacity: 0;
    }
    .header-left {
      display: flex;
      align-items: center;
    }
    .img1 {
      height: 50px; /* Adjusted height for better alignment */
      margin-right: 10px;
    }
    .site-title {
      font-size: 20px;
      font-weight: bold;
      color: #000;
    }
    .header-right {
      display: flex;
      align-items: center;
    }
    .button-container {
      display: flex;
      justify-content: center; /* Center the buttons */
      gap: 20px; /* Add spacing between the buttons */
      margin: 20px 0; /* Add margin above and below the buttons */
    }
    .button-container button {
      padding: 10px 20px;
      font-size: 16px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .button-container button:hover {
      background-color: #0056b3; /* Darker blue on hover */
    }
    .dropdown-container {
        text-align: center;
        margin-top: 20px;
    }
    .dropdown-button {
        padding: 10px 20px;
        font-size: 16px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .dropdown-button:hover {
        background-color: #0056b3;
    }
    .dropdown-content {
        display: none;
        margin-top: 10px;
        position: absolute;
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 10px;
        text-align: left;
        max-height: 300px; /* Limit height for better visibility */
        overflow-y: auto; /* Add scroll for overflow */
    }
    .dropdown-content a {
        display: block;
        padding: 8px 12px;
        text-decoration: none;
        color: #007bff;
    }
    .dropdown-content a:hover {
        background-color: #f1f1f1;
    }
    .main-header {
        position: relative; /* Changed from fixed to relative */
        top: 0;
        left: 0;
        width: 100%;
        color: white;
        z-index: 1000;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px; /* Normal padding */
    }
    .logo-section {
        display: flex;
        align-items: center;
    }
    .logo-section img {
        height: 50px; /* Adjusted height for better alignment */
        margin-right: 10px;
    }
    .logo-section .site-title {
        font-size: 20px;
        font-weight: bold;
        color: #000;
    }
    .user-section {
        display: flex;
        align-items: center;
    }
    .user-role {
        margin-right: 10px; /* Adjusted margin */
        font-weight: bold;
        color: #007bff;
    }
    .logout-button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px; /* Normal padding */
        border-radius: 5px;
        cursor: pointer;
    }
    .logout-button:hover {
        background-color: #0056b3;
    }
    .sub-main {
        background-color: #0630C2; /* Blue background for the sub-header */
        color: white;
        padding: 10px 20px;
        display: flex;
        align-items: center;
    }
    .home-section {
        display: flex;
        align-items: center;
    }
    .home-section img {
        height: 30px; /* Normal height */
        margin-right: 10px; /* Normal margin */
    }
    .home-section .home-text {
        font-size: 18px;
        font-weight: bold;
    }
    .logout-modal {
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

    .logout-modal-content {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        width: 300px;
    }

    .logout-modal-content h3 {
        margin-bottom: 20px;
        font-size: 1.2rem;
        color: #333;
    }

    .logout-modal-content button {
        padding: 10px 20px;
        margin: 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
    }

    .logout-modal-content .confirm-logout {
        background-color: #007bff;
        color: white;
    }

    .logout-modal-content .confirm-logout:hover {
        background-color: #0056b3;
    }

    .logout-modal-content .cancel-logout {
        background-color: #f2f2f2;
        color: #333;
    }

    .logout-modal-content .cancel-logout:hover {
        background-color: #e0e0e0;
    }
    .pagination {
        margin-left: 810px; /* Adjusted margin for better alignment */
        display: flex;
        justify-content: center;
        margin-top: -30px;
    }

    .pagination button {
        padding: 10px 15px;
        margin: 0 5px;
        border: none;
        border-radius: 5px;
        background-color: #007bff;
        color: white;
        cursor: pointer;
        font-size: 1rem;
    }

    .pagination button:hover {
        background-color: #0056b3;
    }

    .pagination button.disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    .chartContainer {
        display: none; /* Hide all chart containers by default */
    }

    .chartContainer.active {
        display: flex; /* Show only the active chart container */
    }
  </style>
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <div class="logo-section">
            <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo">
        </div>
        <div class="user-section">
            <span class="user-role">Admin</span>
            <form action="" method="POST" style="display: inline;">
                <button type="button" class="logout-button" onclick="showLogoutModal()">Logout</button>
            </form>
        </div>
    </div>
    <!-- Removed unused area -->
    <div class="sub-main">
        <div class="home-section">
            <img class="img-home" src="TPC-IMAGES/student.png" alt="Home Icon">
            <span class="home-text">Analytics</span>
        </div>
    </div>
</header>

  <div class="button-container">
      <button onclick="window.location.href='admin_dashboard.php'">Go to Admin Dashboard</button>
      <button onclick="window.location.href='absent_records.php'">View Absent Records</button>
  </div>

  <!-- Dropdown for sections -->
  <div class="dropdown-container">
      <button class="dropdown-button" onclick="toggleDropdown()">Show Sections</button>
      <div id="dropdown-content" class="dropdown-content">
          <?php foreach ($filteredSections as $section => $dates): ?>
              <a href="#section-<?= htmlspecialchars($section) ?>">Section: <?= htmlspecialchars($section) ?></a>
          <?php endforeach; ?>
      </div>
  </div>

  <div id="chartsContainer">
    <!-- Section-specific charts will remain here -->
    <!-- ...existing code for section-specific charts... -->
</div>

<!-- Line Graphs Container -->
<div id="lineGraphsContainer" style="width: 55%; margin-top:-395px; margin-bottom: 20px; margin-left: 800px;">
    <div class="chartContainer">
        <div class="lineGraphContainer" style="flex: 2;">
            <h3>Summary of Total Absences by Date</h3>
            <canvas id="summaryAbsencesLineGraph"></canvas>
        </div>
        <div class="pieChartContainer" style="flex: 1; display: flex; flex-direction: column; align-items: center; margin-left: -75px; margin-top: -30px;">
            <h3>Total Absences (Today, Weekly, Monthly)</h3>
            <canvas id="absencesPieChart" style="width: 100%; height: auto;"></canvas>
        </div>
    </div>
    <div class="chartContainer">
        <div class="lineGraphContainer" style="flex: 2;">
            <h3>Summary of Total Students by Date</h3>
            <canvas id="summaryStudentsLineGraph"></canvas>
        </div>
        <div class="pieChartContainer" style="flex: 1; display: flex; flex-direction: column; align-items: center; margin-left: -75px; margin-top: -30px;">
            <h3>Total Students (Today, Weekly, Monthly)</h3>
            <canvas id="studentsPieChart" style="width: 100%; height: auto;"></canvas>
        </div>
    </div>
</div>

<div class="pagination">
    <button id="prevPage" class="disabled" onclick="changePage(-1)"><</button>
    <button id="nextPage" onclick="changePage(1)">></button>
</div>

<!-- Include Chart.js and the date adapter -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<script>
let absencesData = {};
let presentsData = {};
let totalAbsencesData = {};
let totalStudentsData = {};
let absencesPieData = {};
let studentsPieData = {};
const charts = {};
let summaryAbsencesLineGraph = null;
let summaryStudentsLineGraph = null;
let absencesPieChart = null;
let studentsPieChart = null;

async function fetchAbsencesData() {
    try {
        const response = await fetch('realtime_absences_data.php');
        if (!response.ok) {
            throw new Error('Failed to fetch absences data');
        }
        absencesData = await response.json();
        console.log('Updated absences data:', absencesData);
    } catch (error) {
        console.error('Error fetching absences data:', error);
    }
}

async function fetchPresentsData() {
    try {
        const response = await fetch('realtime_presents_data.php');
        if (!response.ok) {
            throw new Error('Failed to fetch presents data');
        }
        presentsData = await response.json();
        console.log('Updated presents data:', presentsData);
    } catch (error) {
        console.error('Error fetching presents data:', error);
    }
}

async function fetchTotalAbsencesData() {
    try {
        const response = await fetch('realtime_total_absences.php');
        if (!response.ok) {
            throw new Error('Failed to fetch total absences data');
        }
        totalAbsencesData = await response.json();
        console.log('Updated total absences data:', totalAbsencesData);
    } catch (error) {
        console.error('Error fetching total absences data:', error);
    }
}

async function fetchTotalStudentsData() {
    try {
        const response = await fetch('realtime_total_students.php');
        if (!response.ok) {
            throw new Error('Failed to fetch total students data');
        }
        totalStudentsData = await response.json();
        console.log('Updated total students data:', totalStudentsData);
    } catch (error) {
        console.error('Error fetching total students data:', error);
    }
}

async function fetchAbsencesPieData() {
    try {
        const response = await fetch('realtime_absences_pie.php');
        if (!response.ok) {
            throw new Error('Failed to fetch absences pie data');
        }
        absencesPieData = await response.json();
        console.log('Updated absences pie data:', absencesPieData);
    } catch (error) {
        console.error('Error fetching absences pie data:', error);
    }
}

async function fetchStudentsPieData() {
    try {
        const response = await fetch('realtime_presents_pie.php');
        if (!response.ok) {
            throw new Error('Failed to fetch students pie data');
        }
        studentsPieData = await response.json();
        console.log('Updated students pie data:', studentsPieData);
    } catch (error) {
        console.error('Error fetching students pie data:', error);
    }
}

function calculateTimePeriods(data, today, weekAgo, monthAgo) {
    const daily = Object.keys(data).filter(date => date === today).reduce((sum, date) => sum + data[date], 0);
    const weekly = Object.keys(data).filter(date => new Date(date) >= weekAgo).reduce((sum, date) => sum + data[date], 0);
    const monthly = Object.keys(data).filter(date => new Date(date) >= monthAgo).reduce((sum, date) => sum + data[date], 0);
    return [daily, weekly, monthly];
}

function updateCharts() {
    if (!absencesData.absences || !presentsData.presents) {
        console.error('Data is missing required fields.');
        return;
    }

    const chartsContainer = document.getElementById('chartsContainer');

    Object.keys(absencesData.absences).forEach(function(section) {
        const sectionAbsences = absencesData.absences[section];
        const sectionPresents = presentsData.presents[section];

        // Skip sections with no data
        if (!sectionAbsences || Object.keys(sectionAbsences).length === 0) return;
        if (!sectionPresents || Object.keys(sectionPresents).length === 0) return;

        // Check if the section already exists in the DOM
        let sectionContainer = document.getElementById(`section-${section}`);
        if (!sectionContainer) {
            // Create a new section container
            sectionContainer = document.createElement('div');
            sectionContainer.id = `section-${section}`;
            sectionContainer.className = 'sectionContainer'; // Apply the new class
            sectionContainer.style.marginBottom = '40px';
            chartsContainer.appendChild(sectionContainer);

            // Create section header
            const sectionHeader = document.createElement('h3');
            sectionHeader.textContent = `Section: ${section}`;
            sectionContainer.appendChild(sectionHeader);

            // Create absent chart
            const absentChartBox = document.createElement('div');
            absentChartBox.className = 'chartBox';
            const absentCanvas = document.createElement('canvas');
            absentCanvas.id = `absentChart-${section}`;
            absentCanvas.style.flex = '1';
            absentChartBox.appendChild(absentCanvas);
            sectionContainer.appendChild(absentChartBox);

            // Add pie chart for absences inside the section container
            const absentPieContainer = document.createElement('div');
            absentPieContainer.className = 'pieChartContainer';
            const absentPieCanvas = document.createElement('canvas');
            absentPieCanvas.id = `absentPieChart-${section}`;
            absentPieContainer.appendChild(absentPieCanvas);
            sectionContainer.appendChild(absentPieContainer);

            // Create present chart
            const presentChartBox = document.createElement('div');
            presentChartBox.className = 'chartBox';
            const presentCanvas = document.createElement('canvas');
            presentCanvas.id = `presentChart-${section}`;
            presentCanvas.style.flex = '1';
            presentChartBox.appendChild(presentCanvas);
            sectionContainer.appendChild(presentChartBox);

            // Add pie chart for presents inside the section container
            const presentPieContainer = document.createElement('div');
            presentPieContainer.className = 'pieChartContainer';
            const presentPieCanvas = document.createElement('canvas');
            presentPieCanvas.id = `presentPieChart-${section}`;
            presentPieContainer.appendChild(presentPieCanvas);
            sectionContainer.appendChild(presentPieContainer);

            // Initialize charts
            charts[section] = {
                absentChart: new Chart(absentCanvas, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(sectionAbsences),
                        datasets: [{
                            label: 'Total Absences',
                            data: Object.values(sectionAbsences),
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                }),
                presentChart: new Chart(presentCanvas, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(sectionPresents),
                        datasets: [{
                            label: 'Total Presents',
                            data: Object.values(sectionPresents),
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                }),
                absentPieChart: new Chart(absentPieCanvas, {
                    type: 'pie',
                    data: {
                        labels: ['Daily', 'Weekly', 'Monthly'],
                        datasets: [{
                            label: 'Absences',
                            data: [],
                            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                }),
                presentPieChart: new Chart(presentPieCanvas, {
                    type: 'pie',
                    data: {
                        labels: ['Daily', 'Weekly', 'Monthly'],
                        datasets: [{
                            label: 'Presents',
                            data: [],
                            backgroundColor: ['#4CAF50', '#36A2EB', '#FFCE56']
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                })
            };
        }

        // Update bar charts with real-time data
        charts[section].absentChart.data.labels = Object.keys(sectionAbsences);
        charts[section].absentChart.data.datasets[0].data = Object.values(sectionAbsences);
        charts[section].absentChart.update();

        charts[section].presentChart.data.labels = Object.keys(sectionPresents);
        charts[section].presentChart.data.datasets[0].data = Object.values(sectionPresents);
        charts[section].presentChart.update();

        // Update pie charts
        const today = new Date().toISOString().split('T')[0];
        const weekAgo = new Date();
        weekAgo.setDate(weekAgo.getDate() - 7);
        const monthAgo = new Date();
        monthAgo.setMonth(monthAgo.getMonth() - 1);

        const absences = calculateTimePeriods(sectionAbsences, today, weekAgo, monthAgo);
        const presents = calculateTimePeriods(sectionPresents, today, weekAgo, monthAgo);

        charts[section].absentPieChart.data.datasets[0].data = absences;
        charts[section].absentPieChart.update();

        charts[section].presentPieChart.data.datasets[0].data = presents;
        charts[section].presentPieChart.update();
    });
}

function updateAbsencesPieChart() {
    const { daily, weekly, monthly } = absencesPieData;

    if (!absencesPieChart) {
        const ctx = document.getElementById('absencesPieChart').getContext('2d');
        absencesPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Today', 'Weekly', 'Monthly'],
                datasets: [{
                    data: [daily, weekly, monthly],
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    } else {
        absencesPieChart.data.datasets[0].data = [daily, weekly, monthly];
        absencesPieChart.update();
    }
}

function updateStudentsPieChart() {
    const { daily, weekly, monthly } = studentsPieData;

    if (!studentsPieChart) {
        const ctx = document.getElementById('studentsPieChart').getContext('2d');
        studentsPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Today', 'Weekly', 'Monthly'],
                datasets: [{
                    data: [daily, weekly, monthly],
                    backgroundColor: ['#4CAF50', '#36A2EB', '#FFCE56']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    } else {
        studentsPieChart.data.datasets[0].data = [daily, weekly, monthly];
        studentsPieChart.update();
    }
}

function updateSummaryAbsencesLineGraph() {
    const dates = Object.keys(totalAbsencesData).sort();
    const values = dates.map(date => totalAbsencesData[date]);

    if (!summaryAbsencesLineGraph) {
        const ctx = document.getElementById('summaryAbsencesLineGraph').getContext('2d');
        summaryAbsencesLineGraph = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Total Absences by Date',
                    data: values,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } else {
        summaryAbsencesLineGraph.data.labels = dates;
        summaryAbsencesLineGraph.data.datasets[0].data = values;
        summaryAbsencesLineGraph.update();
    }
}

function updateSummaryStudentsLineGraph() {
    const dates = Object.keys(totalStudentsData).sort();
    const values = dates.map(date => totalStudentsData[date]);

    if (!summaryStudentsLineGraph) {
        const ctx = document.getElementById('summaryStudentsLineGraph').getContext('2d');
        summaryStudentsLineGraph = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Total Students by Date',
                    data: values,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } else {
        summaryStudentsLineGraph.data.labels = dates;
        summaryStudentsLineGraph.data.datasets[0].data = values;
        summaryStudentsLineGraph.update();
    }
}

async function fetchAndUpdate() {
    await Promise.all([fetchAbsencesData(), fetchPresentsData(), fetchTotalAbsencesData(), fetchTotalStudentsData(), fetchAbsencesPieData(), fetchStudentsPieData()]);
    updateCharts();
    updateSummaryAbsencesLineGraph();
    updateSummaryStudentsLineGraph();
    updateAbsencesPieChart();
    updateStudentsPieChart();
}

fetchAndUpdate();
setInterval(fetchAndUpdate, 1000); // Update every 1 second

function toggleDropdown() {
    const dropdown = document.getElementById('dropdown-content');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function showLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}

function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

let currentPage = 0;
const chartContainers = document.querySelectorAll('#lineGraphsContainer .chartContainer');
const prevButton = document.getElementById('prevPage');
const nextButton = document.getElementById('nextPage');

function updatePagination() {
    chartContainers.forEach((container, index) => {
        container.classList.toggle('active', index === currentPage);
    });

    prevButton.classList.toggle('disabled', currentPage === 0);
    nextButton.classList.toggle('disabled', currentPage === chartContainers.length - 1);
}

function changePage(direction) {
    if ((direction === -1 && currentPage === 0) || (direction === 1 && currentPage === chartContainers.length - 1)) {
        return;
    }
    currentPage += direction;
    updatePagination();
}

updatePagination();
</script>

<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <h3>Are you sure you want to log out?</h3>
        <form action="" method="POST" style="display: inline;">
            <button type="submit" name="logout" class="confirm-logout">Yes</button>
        </form>
        <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
    </div>
</div>
</body>
</html>