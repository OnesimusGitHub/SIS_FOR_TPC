<?php
session_start();
session_unset();
session_destroy();
header("Location: studentLogin.php"); // Redirect to the student login page
exit;
