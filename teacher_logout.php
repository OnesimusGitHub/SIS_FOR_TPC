<?php
session_start();
session_unset();
session_destroy();
header("Location: teacherLogin.php"); // Redirect to the teacher login page
exit;
