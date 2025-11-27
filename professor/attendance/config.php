<?php
/**
 * Configuration file for professor attendance system
 */

// Include the main application config
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/db_connect.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

// Ensure the professor is logged in
requireRole('professor');

// Database connection function specific to this module
function getAttendanceConnection() {
    return getConnection();
}

?>