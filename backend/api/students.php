<?php
/**
 * Student Management API Endpoint
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
            
            $student_id = sanitize($_POST['student_id'] ?? '');
            $first_name = sanitize($_POST['first_name'] ?? '');
            $last_name = sanitize($_POST['last_name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $group_id = intval($_POST['group_id'] ?? 0); // Get group ID (optional)
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validation
            if (empty($student_id) || empty($first_name) || empty($last_name) || empty($email)) {
                jsonResponse(false, [], 'جميع الحقول مطلوبة (All fields are required)');
            }
            
            if (!isValidEmail($email)) {
                jsonResponse(false, [], 'البريد الإلكتروني غير صحيح (Invalid email address)');
            }
            
            // Set default username if empty
            if (empty($username)) {
                $username = $student_id;
            }
            
            // Set default password if empty
            if (empty($password)) {
                $password = $student_id; // Default password is student ID
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
            
            // Check if student_id already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE student_id = ?");
            $stmt->execute([$student_id]);
            if ($stmt->fetch()) {
                jsonResponse(false, [], 'رقم الطالب موجود بالفعل (Student ID already exists)');
            }
            
            // Check if group exists (if provided)
            if ($group_id > 0) {
                $stmt = $pdo->prepare("SELECT group_id FROM `groups` WHERE group_id = ?");
                $stmt->execute([$group_id]);
                if (!$stmt->fetch()) {
                    jsonResponse(false, [], 'المجموعة غير موجودة (Group not found)');
                }
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $pdo->beginTransaction();
                
                // Insert student
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role, student_id) 
                                       VALUES (?, ?, ?, ?, ?, 'student', ?)");
                $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $student_id]);
                
                $user_id = $pdo->lastInsertId();
                
                // TODO: Add student to courses for this group (optional)
                // This would require getting all courses for this group and enrolling the student
                
                $pdo->commit();
                
                jsonResponse(true, ['user_id' => $user_id], 'تم إضافة الطالب بنجاح (Student added successfully)');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errorCode = $e->getCode();
                if ($errorCode == 23000) {
                    // Duplicate entry
                    if (strpos($e->getMessage(), 'username') !== false) {
                        jsonResponse(false, [], 'اسم المستخدم موجود بالفعل (Username already exists)');
                    } elseif (strpos($e->getMessage(), 'email') !== false) {
                        jsonResponse(false, [], 'البريد الإلكتروني موجود بالفعل (Email already exists)');
                    } elseif (strpos($e->getMessage(), 'student_id') !== false) {
                        jsonResponse(false, [], 'رقم الطالب موجود بالفعل (Student ID already exists)');
                    } else {
                        jsonResponse(false, [], 'البيانات موجودة بالفعل (Duplicate entry)');
                    }
                } else {
                    error_log("Add student error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
                    jsonResponse(false, [], 'خطأ في إضافة الطالب: ' . $e->getMessage());
                }
            }
            break;
            
        case 'get_all':
            requireRole('administrator');
            
            $stmt = $pdo->prepare("SELECT user_id, student_id, first_name, last_name, email, username, created_at 
                                   FROM users WHERE role = 'student' 
                                   ORDER BY last_name, first_name");
            $stmt->execute();
            $students = $stmt->fetchAll();
            
            jsonResponse(true, $students, 'Students retrieved');
            break;
            
        case 'delete':
            requireRole('administrator');
            
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id <= 0) {
                jsonResponse(false, [], 'Invalid student ID');
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
            $stmt->execute([$user_id]);
            
            jsonResponse(true, [], 'Student deleted successfully');
            break;
            
        case 'import_excel':
            requireRole('administrator');
            
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(false, [], 'File upload error');
            }
            
            // This is a simplified version - in production, use PhpSpreadsheet library
            $file = $_FILES['file']['tmp_name'];
            $handle = fopen($file, 'r');
            
            if ($handle === false) {
                jsonResponse(false, [], 'Cannot read file');
            }
            
            $imported = 0;
            $errors = [];
            $line = 0;
            
            $pdo->beginTransaction();
            
            try {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $line++;
                    if ($line === 1) continue; // Skip header
                    
                    if (count($data) < 4) continue;
                    
                    $student_id = sanitize($data[0] ?? '');
                    $first_name = sanitize($data[1] ?? '');
                    $last_name = sanitize($data[2] ?? '');
                    $email = sanitize($data[3] ?? '');
                    
                    if (empty($student_id) || empty($first_name) || empty($last_name) || empty($email)) {
                        $errors[] = "Line $line: Missing required fields";
                        continue;
                    }
                    
                    if (!isValidEmail($email)) {
                        $errors[] = "Line $line: Invalid email";
                        continue;
                    }
                    
                    $password_hash = password_hash($student_id, PASSWORD_DEFAULT);
                    $username = $student_id;
                    
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role, student_id) 
                                               VALUES (?, ?, ?, ?, ?, 'student', ?)");
                        $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $student_id]);
                        $imported++;
                    } catch (PDOException $e) {
                        $errors[] = "Line $line: " . $e->getMessage();
                    }
                }
                
                $pdo->commit();
                fclose($handle);
                
                jsonResponse(true, [
                    'imported' => $imported,
                    'errors' => $errors
                ], "Imported $imported students");
            } catch (Exception $e) {
                $pdo->rollBack();
                fclose($handle);
                jsonResponse(false, [], 'Import failed: ' . $e->getMessage());
            }
            break;
            
        case 'export_excel':
            requireRole('administrator');
            
            $stmt = $pdo->prepare("SELECT student_id, first_name, last_name, email, username 
                                   FROM users WHERE role = 'student' 
                                   ORDER BY last_name, first_name");
            $stmt->execute();
            $students = $stmt->fetchAll();
            
            // Set headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="students.csv"');
            
            // Output CSV
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Student ID', 'First Name', 'Last Name', 'Email', 'Username']);
            foreach ($students as $student) {
                fputcsv($output, $student);
            }
            fclose($output);
            exit;
            
        default:
            jsonResponse(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Students API error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}

?>