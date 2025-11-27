<?php
/**
 * API endpoint for professor attendance system
 * Fetches real student data from database
 */

require_once __DIR__ . '/../../backend/includes/db_connect.php';
require_once __DIR__ . '/../../backend/includes/auth.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

// Check if user is logged in as professor, but don't redirect
if (!isLoggedIn() || !hasRole('professor')) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Please log in as professor.']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getConnection();
    
    switch ($action) {
        case 'get_students':
            // Get course_id from either GET or POST
            $course_id = intval($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
            
            if ($course_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
                exit;
            }
            
            // Debug: Log the course ID
            error_log("Fetching students for course_id: " . $course_id);
            
            // Get all students enrolled in this course
            $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name, c.course_name
                                   FROM enrollments e
                                   JOIN users u ON e.student_id = u.user_id
                                   JOIN courses c ON e.course_id = c.course_id
                                   WHERE e.course_id = ? AND u.role = 'student'
                                   ORDER BY u.last_name, u.first_name");
            $stmt->execute([$course_id]);
            $students = $stmt->fetchAll();
            
            // Debug: Log number of students found
            error_log("Direct enrollments found: " . count($students));
            
            // If no direct enrollments, get students from same group
            if (empty($students)) {
                $stmt = $pdo->prepare("SELECT c.group_id FROM courses c WHERE c.course_id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch();
                
                if ($course && !empty($course['group_id'])) {
                    error_log("Checking group_id: " . $course['group_id']);
                    $stmt = $pdo->prepare("SELECT DISTINCT u.user_id, u.student_id, u.first_name, u.last_name, c.course_name
                                           FROM users u
                                           JOIN enrollments e ON u.user_id = e.student_id
                                           JOIN courses c ON e.course_id = c.course_id
                                           WHERE u.role = 'student' AND c.group_id = ?
                                           ORDER BY u.last_name, u.first_name");
                    $stmt->execute([$course['group_id']]);
                    $students = $stmt->fetchAll();
                    error_log("Group-based students found: " . count($students));
                }
            }
            
            // If still no students, get all students (fallback)
            if (empty($students)) {
                error_log("Using fallback - getting all students for course");
                $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name, c.course_name
                                       FROM users u
                                       JOIN courses c ON c.course_id = ?
                                       WHERE u.role = 'student'
                                       ORDER BY u.last_name, u.first_name
                                       LIMIT 50");
                $stmt->execute([$course_id]);
                $students = $stmt->fetchAll();
                error_log("Fallback students found: " . count($students));
            }
            
            // Debug: Log student data
            error_log("Students data: " . json_encode($students));
            
            // Get attendance data for all sessions of this course
            $stmt = $pdo->prepare("SELECT session_id, session_number FROM attendance_sessions WHERE course_id = ? ORDER BY session_number");
            $stmt->execute([$course_id]);
            $sessions = $stmt->fetchAll();
            
            // Debug: Log sessions data
            error_log("Sessions found: " . count($sessions));
            
            // If no sessions exist, create virtual ones (like in the original system)
            if (empty($sessions)) {
                $sessions = [];
                for ($i = 1; $i <= 6; $i++) {
                    $sessions[] = [
                        'session_id' => $course_id * 100 + $i,
                        'session_number' => $i
                    ];
                }
                error_log("Created virtual sessions");
            }
            
            // Get attendance records for all sessions
            $attendance_data = [];
            $participation_data = [];
            
            foreach ($sessions as $session) {
                $session_id = $session['session_id'];
                
                // Get attendance for this session
                $stmt = $pdo->prepare("SELECT student_id, status FROM attendance_records WHERE session_id = ?");
                $stmt->execute([$session_id]);
                $records = $stmt->fetchAll();
                
                foreach ($records as $record) {
                    $student_id = $record['student_id'];
                    if (!isset($attendance_data[$student_id])) {
                        $attendance_data[$student_id] = [];
                    }
                    $attendance_data[$student_id][$session['session_number']] = $record['status'];
                }
                
                // Get participation for this session
                $stmt = $pdo->prepare("SELECT student_id, participation_type FROM participation_records WHERE session_id = ?");
                $stmt->execute([$session_id]);
                $records = $stmt->fetchAll();
                
                foreach ($records as $record) {
                    $student_id = $record['student_id'];
                    if (!isset($participation_data[$student_id])) {
                        $participation_data[$student_id] = [];
                    }
                    $participation_data[$student_id][$session['session_number']] = $record['participation_type'];
                }
            }
            
            // Debug: Log attendance data
            error_log("Attendance data keys: " . json_encode(array_keys($attendance_data)));
            
            // Format student data for the frontend
            $formatted_students = [];
            foreach ($students as $student) {
                $sessions_array = [];
                $parts_array = [];
                
                // Initialize with default values
                for ($i = 1; $i <= 6; $i++) {
                    // Default to false (absent) if no data
                    $sessions_array[] = isset($attendance_data[$student['user_id']][$i]) && 
                                      ($attendance_data[$student['user_id']][$i] === 'present' || 
                                       $attendance_data[$student['user_id']][$i] === 'late');
                    $parts_array[] = isset($participation_data[$student['user_id']][$i]);
                }
                
                $formatted_students[] = [
                    'id' => $student['student_id'] ?? $student['user_id'],
                    'firstName' => $student['first_name'],
                    'lastName' => $student['last_name'],
                    'course' => $student['course_name'] ?? 'Course Name',
                    'sessions' => $sessions_array,
                    'parts' => $parts_array
                ];
            }
            
            error_log("Formatted students count: " . count($formatted_students));
            
            echo json_encode([
                'success' => true,
                'students' => $formatted_students,
                'sessions' => $sessions
            ]);
            break;
            
        case 'add_student':
            // Professor can add student to their course
            $course_id = intval($_POST['course_id'] ?? 0);
            $student_id = sanitize($_POST['student_id'] ?? '');
            $first_name = sanitize($_POST['first_name'] ?? '');
            $last_name = sanitize($_POST['last_name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            
            // Validation
            if (empty($student_id) || empty($first_name) || empty($last_name) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة (All fields are required)']);
                exit;
            }
            
            if (!isValidEmail($email)) {
                echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صحيح (Invalid email address)']);
                exit;
            }
            
            // Check if professor teaches this course
            $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_id = ? AND professor_id = ?");
            $stmt->execute([$course_id, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Access denied. You do not teach this course.']);
                exit;
            }
            
            // Check if student already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $existing_student = $stmt->fetch();
            
            $user_id = null;
            if ($existing_student) {
                // Student exists, check if they're already enrolled in this course
                $user_id = $existing_student['user_id'];
                $stmt = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ?");
                $stmt->execute([$user_id, $course_id]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'الطالب مسجل بالفعل في هذه المادة (Student already enrolled in this course)']);
                    exit;
                }
            } else {
                // Create new student account
                $username = $student_id;
                $password = $student_id; // Default password is student ID
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $pdo->beginTransaction();
                    
                    // Insert student
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role, student_id) 
                                           VALUES (?, ?, ?, ?, ?, 'student', ?)");
                    $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $student_id]);
                    
                    $user_id = $pdo->lastInsertId();
                    
                    $pdo->commit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errorCode = $e->getCode();
                    if ($errorCode == 23000) {
                        // Duplicate entry
                        if (strpos($e->getMessage(), 'username') !== false) {
                            echo json_encode(['success' => false, 'message' => 'اسم المستخدم موجود بالفعل (Username already exists)']);
                            exit;
                        } elseif (strpos($e->getMessage(), 'email') !== false) {
                            echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني موجود بالفعل (Email already exists)']);
                            exit;
                        } elseif (strpos($e->getMessage(), 'student_id') !== false) {
                            echo json_encode(['success' => false, 'message' => 'رقم الطالب موجود بالفعل (Student ID already exists)']);
                            exit;
                        } else {
                            echo json_encode(['success' => false, 'message' => 'البيانات موجودة بالفعل (Duplicate entry)']);
                            exit;
                        }
                    } else {
                        error_log("Add student error: " . $e->getMessage());
                        echo json_encode(['success' => false, 'message' => 'خطأ في إضافة الطالب: ' . $e->getMessage()]);
                        exit;
                    }
                }
            }
            
            // Enroll student in the course
            if ($user_id) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                    $stmt->execute([$user_id, $course_id]);
                    
                    echo json_encode(['success' => true, 'message' => 'تم إضافة الطالب بنجاح (Student added successfully)']);
                } catch (PDOException $e) {
                    $errorCode = $e->getCode();
                    if ($errorCode == 23000) {
                        echo json_encode(['success' => false, 'message' => 'الطالب مسجل بالفعل في هذه المادة (Student already enrolled in this course)']);
                    } else {
                        error_log("Enrollment error: " . $e->getMessage());
                        echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل الطالب: ' . $e->getMessage()]);
                    }
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'خطأ في إنشاء حساب الطالب (Error creating student account)']);
                exit;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>