<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Define full path to Login.php
$login_path = dirname(__FILE__) . '/Login.php';
if (file_exists($login_path)) {
    header("Location: Login.php");
} else {
    header("Location: ../HTML/Login.php");
}

exit();
?>
