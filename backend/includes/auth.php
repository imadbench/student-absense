<?php
/**
 * Authentication and Authorization Functions
 */

// Ensure no output before JSON responses
if (ob_get_level()) {
    ob_clean();
}

require_once __DIR__ . '/db_connect.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    error_log("requireLogin() called");
    error_log("isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false'));
    error_log("Session data: " . print_r($_SESSION, true));
    
    if (!isLoggedIn()) {
        error_log("User not logged in, checking for AJAX request");
        // For API calls, return JSON error instead of redirecting
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            error_log("AJAX request detected, returning JSON error");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
        error_log("Non-AJAX request, redirecting to login");
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    error_log("User is logged in, continuing");
}

/**
 * Require specific role - redirect if user doesn't have role
 */
function requireRole($role) {
    error_log("requireRole() called with role: $role");
    requireLogin();
    error_log("After requireLogin(), checking role: " . $_SESSION['role'] . " against required: $role");
    error_log("hasRole($role): " . (hasRole($role) ? 'true' : 'false'));
    
    if (!hasRole($role)) {
        error_log("User doesn't have required role, checking for AJAX request");
        // For API calls, return JSON error instead of redirecting
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            error_log("AJAX request detected, returning JSON error");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied. Insufficient permissions.']);
            exit;
        }
        error_log("Non-AJAX request, redirecting to index");
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    error_log("User has required role, continuing");
}

/**
 * Login user
 */
function login($username, $password) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, first_name, last_name, role, student_id 
                               FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['student_id'] = $user['student_id'];
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'error' => 'Invalid username or password'];
    } catch (Exception $e) {
        $logFile = __DIR__ . "/../logs/auth_errors.log";
        $errorMessage = "[" . date('Y-m-d H:i:s') . "] Login error for username '$username': " . $e->getMessage() . "\n";
        error_log($errorMessage, 3, $logFile);
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
}

/**
 * Logout user
 */
function logout() {
    session_unset();
    session_destroy();
    return ['success' => true];
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'role' => $_SESSION['role'],
        'student_id' => $_SESSION['student_id'] ?? null
    ];
}

?>