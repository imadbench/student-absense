<?php
// Simple API test
error_log("Simple API test started");

// Start session
session_start();
error_log("Session started, session ID: " . session_id());

// Set some test session data
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test';
$_SESSION['role'] = 'professor';
error_log("Session data set: " . print_r($_SESSION, true));

// Include the API file
error_log("Including attendance API");
include 'backend/api/attendance.php';
error_log("API inclusion complete");
?>