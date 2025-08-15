<?php
/**
 * Logout Handler
 * Sistem Kehadiran ISN
 */

require_once '../config/config.php';

// Log the logout
if (isset($_SESSION['employee_id'])) {
    // Log logout attempt
    error_log("Employee ID: " . $_SESSION['employee_id'] . " logged out at " . date('Y-m-d H:i:s'));
}

// Clear all session data
session_start();
session_unset();
session_destroy();

// Clear any session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: ../index.php?message=logged_out');
exit();
?> 