<?php
require_once 'config.php';

function getConnection() {
    try {
        // Create PDO connection
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error to file
        error_log("Database connection failed: " . $e->getMessage() . "\n", 3, "db_errors.log");
        
        // Re-throw exception for handling upstream
        throw new Exception("Connection failed: " . $e->getMessage());
    }
}

?>