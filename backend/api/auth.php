<?php
/**
 * Authentication API Endpoint
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            jsonResponse(false, [], 'Username and password are required');
        }
        
        $result = login($username, $password);
        if ($result['success']) {
            jsonResponse(true, $result['user'], 'Login successful');
        } else {
            jsonResponse(false, [], $result['error']);
        }
        break;
        
    case 'logout':
        $result = logout();
        jsonResponse($result['success'], [], 'Logout successful');
        break;
        
    case 'check':
        if (isLoggedIn()) {
            jsonResponse(true, getCurrentUser(), 'User is logged in');
        } else {
            jsonResponse(false, [], 'User is not logged in');
        }
        break;
        
    default:
        jsonResponse(false, [], 'Invalid action');
        break;
}

?>

