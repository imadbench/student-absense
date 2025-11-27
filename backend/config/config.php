<?php
/**
 * Configuration file for Student Attendance Management System
 * Algiers University
 */

// Ensure no output before JSON responses
if (ob_get_level()) {
    ob_clean();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_absence');

// Application configuration
define('APP_NAME', 'Student Attendance Management System');
define('BASE_URL', '/student%20apsence');
define('APP_URL', 'http://localhost/student%20apsence');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false); // Set to true for HTTPS
define('SESSION_HTTP_ONLY', true);
define('SESSION_SAME_SITE', 'Lax');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Algiers');

// Start session if not already started with security settings
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => SESSION_PATH,
        'domain' => SESSION_DOMAIN,
        'secure' => SESSION_SECURE,
        'httponly' => SESSION_HTTP_ONLY,
        'samesite' => SESSION_SAME_SITE
    ]);
    
    // Set additional session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', SESSION_SAME_SITE);
    
    session_start();
    
    // Regenerate session ID for security
    if (!isset($_SESSION['initialized'])) {
        session_regenerate_id(true);
        $_SESSION['initialized'] = true;
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    // Validate session for security
    if (isset($_SESSION['user_id'])) {
        $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check for session hijacking attempts
        if ($_SESSION['ip_address'] !== $current_ip || $_SESSION['user_agent'] !== $current_ua) {
            // Potential session hijacking - destroy session
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['initialized'] = true;
            $_SESSION['ip_address'] = $current_ip;
            $_SESSION['user_agent'] = $current_ua;
        }
    }
}

?>