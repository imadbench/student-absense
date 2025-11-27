<?php
/**
 * Courses API Endpoint
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
            
            // Get and sanitize input data
            $course_code = sanitize($_POST['course_code'] ?? '');
            $course_name = sanitize($_POST['course_name'] ?? '');
            $professor_id = intval($_POST['professor_id'] ?? 0);
            $group_id = intval($_POST['group_id'] ?? 0);
            $academic_year = sanitize($_POST['academic_year'] ?? '');
            $semester = sanitize($_POST['semester'] ?? '');
            $course_day = sanitize($_POST['course_day'] ?? '');
            $course_time = sanitize($_POST['course_time'] ?? '');
            
            // Validation
            if (empty($course_code) || empty($course_name) || $professor_id <= 0 || $group_id <= 0 || empty($academic_year) || empty($semester)) {
                jsonResponse(false, [], 'جميع الحقول مطلوبة (All fields are required)');
            }
            
            // Validate semester
            $valid_semesters = ['Fall', 'Spring', 'Summer'];
            if (!in_array($semester, $valid_semesters)) {
                jsonResponse(false, [], 'الفصل الدراسي غير صحيح (Invalid semester)');
            }
            
            // Validate course day if provided
            if (!empty($course_day)) {
                $valid_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                if (!in_array($course_day, $valid_days)) {
                    jsonResponse(false, [], 'يوم المادة غير صحيح (Invalid course day)');
                }
            }
            
            // Check if course code already exists
            $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ?");
            $stmt->execute([$course_code]);
            if ($stmt->fetch()) {
                jsonResponse(false, [], 'رمز المادة موجود بالفعل (Course code already exists)');
            }
            
            // Check if professor exists and is a professor
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'professor'");
            $stmt->execute([$professor_id]);
            if (!$stmt->fetch()) {
                jsonResponse(false, [], 'الأستاذ غير موجود أو ليس أستاذاً (Professor not found or not a professor)');
            }
            
            // Check if group exists
            $stmt = $pdo->prepare("SELECT group_id FROM `groups` WHERE group_id = ?");
            $stmt->execute([$group_id]);
            if (!$stmt->fetch()) {
                jsonResponse(false, [], 'المجموعة غير موجودة (Group not found)');
            }
            
            try {
                // Insert the new course
                $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, professor_id, group_id, academic_year, semester, course_day, course_time) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$course_code, $course_name, $professor_id, $group_id, $academic_year, $semester, $course_day ?: null, $course_time ?: null]);
                
                $course_id = $pdo->lastInsertId();
                
                jsonResponse(true, ['course_id' => $course_id], 'تمت إضافة المادة بنجاح (Course added successfully)');
            } catch (PDOException $e) {
                $errorCode = $e->getCode();
                if ($errorCode == 23000) {
                    // Duplicate entry
                    if (strpos($e->getMessage(), 'course_code') !== false) {
                        jsonResponse(false, [], 'رمز المادة موجود بالفعل (Course code already exists)');
                    } else {
                        jsonResponse(false, [], 'البيانات موجودة بالفعل (Duplicate entry)');
                    }
                } else {
                    error_log("Add course error: " . $e->getMessage());
                    jsonResponse(false, [], 'خطأ في إضافة المادة: ' . $e->getMessage());
                }
            }
            break;
            
        case 'get_professor_courses':
            requireRole('professor');
            
            try {
                $stmt = $pdo->prepare("SELECT c.*, g.group_name, g.group_code 
                                       FROM courses c
                                       LEFT JOIN `groups` g ON c.group_id = g.group_id
                                       WHERE c.professor_id = ?
                                       ORDER BY c.academic_year DESC, c.course_name");
                $stmt->execute([$_SESSION['user_id']]);
                $courses = $stmt->fetchAll();
                
                jsonResponse(true, $courses, 'Courses retrieved');
            } catch (Exception $e) {
                error_log("Professor courses query error: " . $e->getMessage());
                jsonResponse(false, [], 'Failed to retrieve courses: ' . $e->getMessage());
            }
            break;
            
        case 'get_student_courses':
            if (!hasRole('student')) {
                jsonResponse(false, [], 'Access denied: User is not a student');
            }
            
            try {
                // First check if the student exists
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'student'");
                $stmt->execute([$_SESSION['user_id']]);
                if (!$stmt->fetch()) {
                    jsonResponse(false, [], 'Student not found in database');
                }
                
                // Get courses the student is directly enrolled in
                $stmt = $pdo->prepare("SELECT c.*, g.group_name, g.group_code, u.first_name as professor_first, u.last_name as professor_last
                                       FROM enrollments e
                                       JOIN courses c ON e.course_id = c.course_id
                                       LEFT JOIN `groups` g ON c.group_id = g.group_id
                                       LEFT JOIN users u ON c.professor_id = u.user_id
                                       WHERE e.student_id = ?
                                       ORDER BY c.academic_year DESC, c.course_name");
                $stmt->execute([$_SESSION['user_id']]);
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // If no direct enrollments found, try to get courses from student's group
                if (empty($courses)) {
                    // Get the groups this student belongs to through any enrollments
                    $stmt = $pdo->prepare("SELECT DISTINCT g.group_id, g.group_name, g.group_code
                                           FROM enrollments e
                                           JOIN courses c ON e.course_id = c.course_id
                                           JOIN `groups` g ON c.group_id = g.group_id
                                           WHERE e.student_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $studentGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // If student has groups, get all courses from those groups
                    if (!empty($studentGroups)) {
                        $groupIds = array_column($studentGroups, 'group_id');
                        $placeholders = str_repeat('?,', count($groupIds) - 1) . '?';
                        
                        // Get all courses from the student's groups
                        $stmt = $pdo->prepare("SELECT DISTINCT c.*, g.group_name, g.group_code, u.first_name as professor_first, u.last_name as professor_last 
                                               FROM courses c
                                               LEFT JOIN `groups` g ON c.group_id = g.group_id
                                               LEFT JOIN users u ON c.professor_id = u.user_id
                                               WHERE c.group_id IN ($placeholders)
                                               ORDER BY c.academic_year DESC, c.course_name");
                        $stmt->execute($groupIds);
                        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        // If no enrollments found, get all courses (fallback)
                        $stmt = $pdo->prepare("SELECT c.*, g.group_name, g.group_code, u.first_name as professor_first, u.last_name as professor_last 
                                               FROM courses c
                                               LEFT JOIN `groups` g ON c.group_id = g.group_id
                                               LEFT JOIN users u ON c.professor_id = u.user_id
                                               ORDER BY c.academic_year DESC, c.course_name");
                        $stmt->execute();
                        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
                
                jsonResponse(true, $courses, 'Courses retrieved');
            } catch (Exception $e) {
                error_log("Student courses query error: " . $e->getMessage());
                jsonResponse(false, [], 'Failed to retrieve courses: ' . $e->getMessage());
            }
            break;
            
        case 'get_all':
            requireRole('administrator');
            
            try {
                $stmt = $pdo->prepare("SELECT c.*, g.group_name, g.group_code, 
                                       u.first_name as professor_first, u.last_name as professor_last 
                                       FROM courses c
                                       LEFT JOIN `groups` g ON c.group_id = g.group_id
                                       LEFT JOIN users u ON c.professor_id = u.user_id
                                       ORDER BY c.academic_year DESC, c.course_name");
                $stmt->execute();
                $courses = $stmt->fetchAll();
                
                jsonResponse(true, $courses, 'Courses retrieved');
            } catch (Exception $e) {
                error_log("All courses query error: " . $e->getMessage());
                jsonResponse(false, [], 'Failed to retrieve courses: ' . $e->getMessage());
            }
            break;
            
        case 'get_enrolled_students':
            $course_id = intval($_GET['course_id'] ?? 0);
            
            if ($course_id <= 0) {
                jsonResponse(false, [], 'Invalid course ID');
            }
            
            // Check if user has access to this course
            if (hasRole('professor')) {
                $stmt = $pdo->prepare("SELECT c.*, g.group_id FROM courses c 
                                      LEFT JOIN `groups` g ON c.group_id = g.group_id 
                                      WHERE c.course_id = ? AND c.professor_id = ?");
                $stmt->execute([$course_id, $_SESSION['user_id']]);
                $course = $stmt->fetch();
                if (!$course) {
                    jsonResponse(false, [], 'Access denied');
                    break;
                }
            } else {
                $stmt = $pdo->prepare("SELECT c.*, g.group_id FROM courses c 
                                      LEFT JOIN `groups` g ON c.group_id = g.group_id 
                                      WHERE c.course_id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch();
            }
            
            try {
                // First try to get enrolled students
                $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email 
                                       FROM enrollments e
                                       JOIN users u ON e.student_id = u.user_id
                                       WHERE e.course_id = ?
                                       ORDER BY u.last_name, u.first_name");
                $stmt->execute([$course_id]);
                $students = $stmt->fetchAll();
                
                // If no students are enrolled, get all students who should be in this course's group
                if (empty($students) && !empty($course['group_id'])) {
                    // First try: Get all students who are enrolled in any course in the same group
                    $stmt = $pdo->prepare("SELECT DISTINCT u.user_id, u.student_id, u.first_name, u.last_name, u.email 
                                           FROM users u
                                           WHERE u.role = 'student' 
                                           AND u.user_id IN (
                                               SELECT e.student_id 
                                               FROM enrollments e 
                                               JOIN courses c ON e.course_id = c.course_id 
                                               WHERE c.group_id = ?
                                           )
                                           ORDER BY u.last_name, u.first_name");
                    $stmt->execute([$course['group_id']]);
                    $students = $stmt->fetchAll();
                    
                    // If still no students found, get ALL students in this group regardless of enrollments
                    if (empty($students)) {
                        error_log("No enrolled students found for group_id: " . $course['group_id'] . " - Getting all students in group");
                        // This is a fallback for new courses with no enrollments yet
                        // We need to get students who belong to this group (assuming group assignment is stored somewhere)
                        // For now, get all students as a last resort
                        $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email 
                                               FROM users u
                                               WHERE u.role = 'student'
                                               ORDER BY u.last_name, u.first_name");
                        $stmt->execute();
                        $students = $stmt->fetchAll();
                        
                        if (!empty($students)) {
                            error_log("Found " . count($students) . " students as fallback for new course");
                        }
                    }
                }
                
                jsonResponse(true, $students, 'Students retrieved');
            } catch (Exception $e) {
                error_log("Enrolled students query error: " . $e->getMessage());
                jsonResponse(false, [], 'Failed to retrieve students: ' . $e->getMessage());
            }
            break;
            
        case 'enroll_group_students':
            requireRole('administrator');
            
            $course_id = intval($_POST['course_id'] ?? 0);
            
            if ($course_id <= 0) {
                jsonResponse(false, [], 'Invalid course ID');
            }
            
            try {
                $pdo->beginTransaction();
                
                // Get course info to find group_id
                $stmt = $pdo->prepare("SELECT group_id FROM courses WHERE course_id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch();
                
                if (!$course || empty($course['group_id'])) {
                    $pdo->rollback();
                    jsonResponse(false, [], 'Course not found or has no group');
                }
                
                // Get all students in this group who are not already enrolled
                $stmt = $pdo->prepare("SELECT u.user_id 
                                       FROM users u
                                       WHERE u.role = 'student' 
                                       AND u.user_id NOT IN (
                                           SELECT e.student_id 
                                           FROM enrollments e 
                                           WHERE e.course_id = ?
                                       )
                                       AND u.user_id IN (
                                           SELECT e.student_id 
                                           FROM enrollments e 
                                           JOIN courses c ON e.course_id = c.course_id 
                                           WHERE c.group_id = ?
                                       )");
                $stmt->execute([$course_id, $course['group_id']]);
                $studentsToEnroll = $stmt->fetchAll();
                
                // Enroll each student
                foreach ($studentsToEnroll as $student) {
                    $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                    $stmt->execute([$student['user_id'], $course_id]);
                }
                
                $pdo->commit();
                
                jsonResponse(true, ['enrolled_count' => count($studentsToEnroll)], 'Students enrolled successfully');
            } catch (Exception $e) {
                $pdo->rollback();
                error_log("Enroll group students error: " . $e->getMessage());
                jsonResponse(false, [], 'Failed to enroll students: ' . $e->getMessage());
            }
            break;
            
        default:
            jsonResponse(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Courses API error: " . $e->getMessage());
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}