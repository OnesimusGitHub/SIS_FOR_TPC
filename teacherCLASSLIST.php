<?php
session_start();

// Redirect to login page if the teacher is not logged in
if (!isset($_SESSION["teacherid"]) || empty($_SESSION["teacherid"])) {
    header("Location: teacherLogin.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: teacherLogin.php'); // Redirect to login page
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Classlist</title>
    

<style>
         * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
        }
        
        .main-header {
            border-bottom: 1px solid #ccc;
        }
        
        .img-main {
            height: 8vh;
        }
        
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: black;
        }
        
        .user-section {
            display: flex;
            align-items: center;
        }
        
        .user-role {
            font-size: 16px;
            color: #0078D4; /* Blue color for the text */
            margin-right: 15px;
        }
        
        .logout-button {
            background-color: #0055a5; /* Dark blue background */
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .logout-button:hover {
            background-color: #003f7f; /* Slightly darker blue on hover */
        }
        
        .sub-main {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50px;
            width: 100%;
        }
        
        .sub-header {
            width: 97%;
            display: flex;
            align-items: center;
            background-color: #0033cc; /* Blue background */
            padding: 10px 20px;
            color: white;
            height: 50px;
        }
        
        .menu-icon {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 20px;
            cursor: pointer;
            margin-right: 20px;
        }
        
        .menu-icon .line {
            width: 25px;
            height: 3px;
            background-color: white;
            border-radius: 2px;
        }
        
        .home-section {
            display: flex;
            align-items: center;
        }
        
        .home-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        
        .home-text {
            font-size: 22px;
            font-weight: bold;
            color: white;
        }
        
        .img-home {
            height: 30px;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
          }
          .container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          }
          .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
          }
          .profile-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 20px;
          }
          .profile-header h1 {
            font-size: 24px;
            margin: 0;
          }
          .profile-header p {
            margin: 5px 0 0;
            color: #666;
          }
          .section {
            display: flex;
            justify-content: space-between;
          }
          .section div {
            width: 48%;
          }
          .section label {
            font-size: 14px;
            color: #666;
          }
          .section input {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
          }

/* Side Menu */
.side-menu {
    display: none; /* Initially hidden */
    position: fixed;
    top: 0;
    left: 0;
    width: 270px; /* Width of the side menu */
    height: 100%;
    background-color: #0033cc; /* Blue background */
    color: white;
    padding: 20px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    overflow-y: auto;
}

.side-menu .close-button {
    background: none;
    border: none;
    color: white;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
    display: block;
    margin-bottom: 20px;
}

.side-menu ul {
    list-style: none;
    padding: 0;
}

.side-menu ul li {
    margin: 15px 0;
}

.side-menu ul li.menu-section-title {
    font-size: 14px;
    text-transform: uppercase;
    font-weight: bold;
    margin-top: 20px;
    color: white;
    border-top: 1px solid white;
    padding-top: 10px;
}

.side-menu ul li a {
    text-decoration: none;
    display: block;
    background-color: #cce0ff; /* Light blue for buttons */
    color: #003366; /* Dark blue text */
    padding: 10px 15px;
    border-radius: 5px;
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.side-menu ul li a:hover {
    background-color: #b3ccff; /* Slightly darker on hover */
}

/* Open and Close Animations */
.side-menu.open {
    display: block;
    animation: slideIn 0.3s forwards;
}

@keyframes slideIn {
    from {
        transform: translateX(-300px);
    }
    to {
        transform: translateX(0);
    }
}


.main{
    border: 1px solid red;
    height: 10vh;
    width: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.left{
    border: 1px solid red;
    height: 10vh;
    width: 25%;
}
.right{
    border: 1px solid red;
    height: 10vh;
    width: 25%;
}

/* Main Container */
.main-container {
    display: flex;
    flex-direction: row;
    gap: 20px;
    padding: 20px;
}

/* Left Section List */
.section-list {
    width: 20%;
    background-color: #f1f1f1;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.section-list h3 {
    text-align: center;
    color: #184575;
    margin-bottom: 10px;
}

.section-list ul {
    list-style: none;
    padding: 0;
}

.section-list li {
    margin: 10px 0;
}

.section-list .section-link {
    text-decoration: none;
    color: #175495;
    font-weight: bold;
    display: block;
    text-align: center;
    padding: 10px;
    border: 1px solid #175495;
    border-radius: 4px;
    transition: background-color 0.3s, color 0.3s;
}

.section-list .section-link:hover {
    background-color: #2874c5;
    color: white;
}

/* Classlist Container */
.classlist-container {
    width: 75%;
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Classlist Header */
.classlist-header {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 10px;
    font-size: 14px;
    color: #555;
}

/* Classlist Table */
.classlist-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.classlist-table th,
.classlist-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
}

.classlist-table th {
    background-color: #3b48ad;
    color: white;
    font-weight: bold;
}

.classlist-table tr:nth-child(even) {
    background-color: #f2f2f2;
}

.classlist-table tr:hover {
    background-color: #ddd;
}

.loading-message {
    text-align: center;
    font-size: 16px;
    color: #555;
    margin-top: 20px;
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

</style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <!-- Top Header -->
        <div class="top-header">
            <div class="logo-section">
                <img class="img-main" src="TPC-IMAGES/Screenshot 2024-11-08 173600.png" alt="Logo" class="logo">
            </div>
            <div class="user-section">
                <span class="user-role">Teacher</span>
                <a href="javascript:void(0);" class="logout-button" onclick="showLogoutModal()">Logout</a> <!-- Logout button -->
            </div>
        </div>
        <br>
        <!-- Sub Header -->
        <div class="sub-main">
            <div class="sub-header">
                <div class="menu-icon" id="burger-menu">
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>
                <div class="home-section">
                    <img class="img-home" src="TPC-IMAGES/teacher1-removebg-preview.png" alt="Home Icon" class="home-icon">
                    <span class="home-text">CLASSLIST</span>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <!-- Left Section List -->
        <div class="section-list">
            <h3>Sections</h3>
            <ul id="sectionList">
                <!-- Sections will be dynamically loaded here -->
            </ul>
        </div>

        <!-- Classlist Section -->
        <div class="classlist-container">
            <h1 id="classHeader">Class : Select a Section</h1>
            <div class="classlist-header">
                <span id="current-date"></span>
            </div>
            <table class="classlist-table">
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Strand</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody id="classlistTableBody">
                    <tr>
                        <td colspan="5" class="loading-message">Select a section to view students.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

   <!-- Side Menu -->
   <nav class="side-menu" id="side-menu">
        <button class="close-button" id="close-menu">&times;</button>
        <ul>
            <li><a href="teacher_dashboard.php" class="menu-item">Dashboard</a></li>
            <li class="menu-section-title">MY</li>
            <li><a href="teacherPROFILE.php" class="menu-item">Profile</a></li>
            <li><a href="teacherSCHEDULE.php" class="menu-item">Schedule</a></li>
            <li><a href="teacherCLASSLIST.php" class="menu-item">Classlist</a></li>
            <li><a href="teacherHome.php" class="menu-item">Announcements</a></li> <!-- Added link to teacherHome.php -->
            <li class="menu-section-title">NAVIGATION</li>
            <li><a href="teacherMAP.php" class="menu-item">Campus Map</a></li>
        </ul>
    </nav>

    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <h3>Are you sure you want to log out?</h3>
            <form action="" method="GET" style="display: inline;">
                <button type="submit" name="logout" value="true" class="confirm-logout">Yes</button>
            </form>
            <button class="cancel-logout" onclick="closeLogoutModal()">No</button>
        </div>
    </div>

    <script>
        const burgerMenu = document.getElementById('burger-menu');
const sideMenu = document.getElementById('side-menu');
const closeMenu = document.getElementById('close-menu');

// Open the menu when clicking the burger icon
burgerMenu.addEventListener('click', function () {
    sideMenu.classList.add('open');
});

// Close the menu when clicking the close button
closeMenu.addEventListener('click', function () {
    sideMenu.classList.remove('open');
});

// Display the current date in the classlist header
document.addEventListener("DOMContentLoaded", () => {
    const currentDateElement = document.getElementById("current-date");
    const currentDate = new Date().toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    });
    currentDateElement.textContent = `Date: ${currentDate}`;
});

const sectionList = document.getElementById("sectionList");
const classHeader = document.getElementById("classHeader");
const classlistTableBody = document.getElementById("classlistTableBody");

// Fetch sections and students in real-time
async function fetchTeacherSectionsAndStudents() {
    try {
        const response = await fetch("fetch_teacherside_sections.php");
        if (!response.ok) {
            throw new Error("Failed to fetch sections and students.");
        }
        const data = await response.json();

        if (data.success) {
            sectionList.innerHTML = ""; // Clear existing sections
            if (data.sections.length > 0) {
                data.sections.forEach(section => {
                    const li = document.createElement("li");
                    li.innerHTML = `<a href="#" class="section-link" data-section-id="${section.section_ID}">${section.section_Name}</a>`;
                    sectionList.appendChild(li);

                    // Add click event listener to each section link
                    li.querySelector(".section-link").addEventListener("click", event => {
                        event.preventDefault();
                        displayStudents(section.section_Name, section.students);
                    });
                });
            } else {
                sectionList.innerHTML = "<li>No sections assigned to you.</li>";
            }
        } else {
            sectionList.innerHTML = `<li>${data.message}</li>`;
        }
    } catch (error) {
        console.error("Error fetching sections and students:", error);
        sectionList.innerHTML = "<li>Error loading sections.</li>";
    }
}

// Display students for the selected section
function displayStudents(sectionName, students) {
    classHeader.textContent = `Class : ${sectionName}`;
    classlistTableBody.innerHTML = ""; // Clear existing rows

    const totalStudents = students.length; // Calculate total students
    const totalStudentsElement = document.createElement("p");
    totalStudentsElement.textContent = `Total Students: ${totalStudents}`;
    totalStudentsElement.style.fontWeight = "bold";
    totalStudentsElement.style.marginTop = "10px";
    classHeader.appendChild(totalStudentsElement); // Append below the header

    if (students.length > 0) {
        students.forEach(student => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${student.shsstud_ID}</td>
                <td>${student.first_name}</td>
                <td>${student.last_name}</td>
                <td>${student.strand_code}</td>
                <td>${student.grade}</td>
            `;
            classlistTableBody.appendChild(row);
        });
    } else {
        classlistTableBody.innerHTML = `<tr><td colspan="5" class="loading-message">No students found for this section.</td></tr>`;
    }
}

// Display the current date in the classlist header
document.addEventListener("DOMContentLoaded", () => {
    const currentDateElement = document.getElementById("current-date");
    const currentDate = new Date().toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    });
    currentDateElement.textContent = `Date: ${currentDate}`;

    // Fetch sections and students on page load
    fetchTeacherSectionsAndStudents();
});

    function showLogoutModal() {
        document.getElementById('logoutModal').style.display = 'flex';
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }

        // Logout function
        function logout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "teacherCLASSLIST.php?action=logout";
            }
        }

    </script>
</body>
</html>