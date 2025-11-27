<?php
/**
 * Database Connection Handler
 * Handles PDO connection with proper error handling
 */

// Ensure no output before JSON responses
if (ob_get_level()) {
    ob_clean();
}

require_once __DIR__ . '/../config/config.php';

function getConnection() {
    try {
        // Create PDO connection
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error to file with proper path
        $logFile = __DIR__ . "/../logs/db_errors.log";
        $errorMessage = "[" . date('Y-m-d H:i:s') . "] Database connection failed: " . $e->getMessage() . "\n";
        error_log($errorMessage, 3, $logFile);
        
        // Return JSON error for API calls
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit;
        }
        
        // Re-throw exception for handling upstream
        throw new Exception("Connection failed: " . $e->getMessage());
    }
}

// Test connection function
function testConnection() {
    try {
        $pdo = getConnection();
        return ['success' => true, 'message' => 'Connection successful'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

?>