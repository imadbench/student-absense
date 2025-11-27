<?php
/**
 * Groups Management API Endpoint
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getConnection();
    
    switch ($action) {
        case 'get_all':
            requireRole('administrator');
            
            $stmt = $pdo->prepare("SELECT group_id, group_name, group_code, description 
                                   FROM `groups` 
                                   ORDER BY group_name");
            $stmt->execute();
            $groups = $stmt->fetchAll();
            
            jsonResponse(true, $groups, 'Groups retrieved');
            break;
            
        default:
            jsonResponse(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Groups API error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}

?>