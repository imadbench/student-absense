<?php
/**
 * Professors Management API Endpoint
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
        case 'add':
            requireRole('administrator');
            
            $first_name = sanitize($_POST['first_name'] ?? '');
            $last_name = sanitize($_POST['last_name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validation
            if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
                jsonResponse(false, [], 'جميع الحقول مطلوبة (All fields are required)');
            }
            
            if (!isValidEmail($email)) {
                jsonResponse(false, [], 'البريد الإلكتروني غير صحيح (Invalid email address)');
            }
            
            if (strlen($password) < 6) {
                jsonResponse(false, [], 'كلمة المرور يجب أن تكون 6 أحرف على الأقل (Password must be at least 6 characters)');
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                jsonResponse(false, [], 'اسم المستخدم موجود بالفعل (Username already exists)');
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                jsonResponse(false, [], 'البريد الإلكتروني موجود بالفعل (Email already exists)');
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
                                       VALUES (?, ?, ?, ?, ?, 'professor')");
                $stmt->execute([$username, $email, $password_hash, $first_name, $last_name]);
                
                $user_id = $pdo->lastInsertId();
                jsonResponse(true, ['user_id' => $user_id], 'تم إضافة الأستاذ بنجاح (Professor added successfully)');
            } catch (PDOException $e) {
                $errorCode = $e->getCode();
                if ($errorCode == 23000) {
                    // Duplicate entry
                    if (strpos($e->getMessage(), 'username') !== false) {
                        jsonResponse(false, [], 'اسم المستخدم موجود بالفعل (Username already exists)');
                    } elseif (strpos($e->getMessage(), 'email') !== false) {
                        jsonResponse(false, [], 'البريد الإلكتروني موجود بالفعل (Email already exists)');
                    } else {
                        jsonResponse(false, [], 'البيانات موجودة بالفعل (Duplicate entry)');
                    }
                } else {
                    error_log("Add professor error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
                    jsonResponse(false, [], 'خطأ في إضافة الأستاذ: ' . $e->getMessage());
                }
            }
            break;
            
        case 'get_all':
            requireRole('administrator');
            
            $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, username, created_at 
                                   FROM users WHERE role = 'professor' 
                                   ORDER BY last_name, first_name");
            $stmt->execute();
            $professors = $stmt->fetchAll();
            
            jsonResponse(true, $professors, 'Professors retrieved');
            break;
            
        case 'delete':
            requireRole('administrator');
            
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id <= 0) {
                jsonResponse(false, [], 'Invalid professor ID');
            }
            
            // Check if professor has courses
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses WHERE professor_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                jsonResponse(false, [], 'Cannot delete professor. They have assigned courses. Please reassign courses first.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'professor'");
            $stmt->execute([$user_id]);
            
            jsonResponse(true, [], 'Professor deleted successfully');
            break;
            
        default:
            jsonResponse(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Professors API error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}

?>

