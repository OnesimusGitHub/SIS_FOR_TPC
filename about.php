<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="about.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <title>Landing page</title>
</head>
<body>
    <div class="header1">
        <div class="header-left">
            <img class="img1" src="TPC-IMAGES/logos-removebg-preview.png" alt="SIS-TPC Logo">
            <span class="site-title">SIS-TPC</span>
        </div>
        <div class="header-right">
            <div class="nav-links">
                <a href="homepage.php" class="home">Home</a>
                <a href="program.php" class="program">Programs</a>
                <div class="dropdown">
                    <a class="about">About Us &#9662;</a>
                    <div class="dropdown-content">
                        <a href="about.php">Mission And Vision</a>
                        <a href="hymn.php">TPC Hymn</a>
                    </div>
                </div>
                <a href="ALLChoose.php" class="loginportal">Login Portal &#x1F517;</a>
            </div>
        </div>
    </div>
    <div class="main" style="background-image: url('TPC-IMAGES/ahj.PNG'); background-size: cover; background-position: center;">
        <div class="main-content">
            <h1>About Us</h1>
            <p>Learn more about Trinity Polytechnic College and our commitment to excellence in education.</p>
        </div>
    </div>
    <div class="content">
        <h1>Mission and Vision</h1>
        <div class="programs">
            <div class="program-card">
                <img src="TPC-IMAGES/img2.jpg" alt="Program 1">
                <div class="program-label">Program 1</div>
            </div>
            <div class="program-card">
                <img src="TPC-IMAGES/img3.jpg" alt="Program 2">
                <div class="program-label">Program 2</div>
            </div>
        </div>
    </div>
    <div class="vision-mission">
        <div class="vision">
            <h2>Our Vision</h2>
            <p>TO BE A PREMIER EDUCATIONAL INSTITUTION WHICH AIMS TO IMBUE ITS ACADEMIC COMMUNITY WITH QUALITY AND RELEVANT EDUCATION, WORLD-CLASS TRAINING AND DEVELOPMENT AND QUALITY SERVICES RESPONSIVE TO LOCAL, NATIONAL AND GLOBAL DEVELOPMENT.</p>
        </div>
        <div class="mission">
            <h2>Our Mission</h2>
            <p>TO PROVIDE QUALITY AND RELEVANT EDUCATION THAT PREPARES LEARNERS TO BECOME COMPASSIONATE AND EFFECTIVE LEADERS, LIFE-LONG LEARNERS AND PRODUCTIVE CITIZENS GUIDED WITH THE VIRTUES AND VALUES BY THE ALMIGHTY.</p>
        </div>
    </div>
    <div class="goals-objectives">
        <h2>Goals and Objectives</h2>
        <div class="goals-container">
            <div class="goal-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <p>To employ highly-qualified teaching personnel and research faculty.</p>
            </div>
            <div class="goal-item">
                <i class="fas fa-industry"></i>
                <p>To facilitate industry-based faculty development programs.</p>
            </div>
            <div class="goal-item">
                <i class="fas fa-book"></i>
                <p>To develop up-to-date curricula and course materials.</p>
            </div>
            <div class="goal-item">
                <i class="fas fa-user-graduate"></i>
                <p>To cater quality student services.</p>
            </div>
            <div class="goal-item">
                <i class="fas fa-hands-helping"></i>
                <p>To enjoin community outreach/extension programs.</p>
            </div>
            <div class="goal-item">
                <i class="fas fa-building"></i>
                <p>To provide state-of-the-art facilities.</p>
            </div>
            <div class="goal-item">
                <i class="fas fa-heart"></i>
                <p>To improve the quality of living of the stakeholders.</p>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="footer-left">
            <img src="TPC-IMAGES/logos-removebg-preview.png" alt="Trinity Polytechnic College Logo">
            <h3>Trinity Polytechnic College</h3>
            <p>@ Directory</p>
            <p><strong>Novaliches Campus:</strong> 892 Alfina Building, Quirino Highway, Brgy. Gulod, Novaliches, Quezon City</p>
            <p><strong>North Caloocan Campus:</strong> Bow Valley College</p>
        </div>
        <div class="footer-right">
            <h3>Contact Us</h3>
            <p>Email: tpcnova@gmail.com</p>
            <p>Phone: 0927-805-1652 / 0947-3646-906</p>
            <div class="social-icons">
                <a href="#"><img src="TPC-IMAGES/facebook.png" alt="Facebook"></a>
                <a href="#"><img src="TPC-IMAGES/twitter.png" alt="Twitter"></a>
                <a href="#"><img src="TPC-IMAGES/instagram.png" alt="Instagram"></a>
                <a href="#"><img src="TPC-IMAGES/linkedin-icon.png" alt="LinkedIn"></a>
            </div>
            <h4>Resources</h4>
            <p>Facebook | Twitter | Instagram</p>
        </div>
    </footer>
    <script src="about.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init(); // Initialize AOS
    </script>
</body>
</html>