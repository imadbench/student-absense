<?php
/**
 * Justification API Endpoint
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
        case 'submit':
            if (!hasRole('student')) {
                jsonResponse(false, [], 'Only students can submit justifications');
            }
            
            $session_id = intval($_POST['session_id'] ?? 0);
            $reason = sanitize($_POST['reason'] ?? '');
            
            if ($session_id <= 0 || empty($reason)) {
                jsonResponse(false, [], 'Session ID and reason are required');
            }
            
            // Validate that this is a valid virtual session ID
            $course_id = intval($session_id / 100);
            $session_number = $session_id % 100;
            
            if ($session_number < 1 || $session_number > 6 || $course_id <= 0) {
                jsonResponse(false, [], 'Invalid session ID');
            }
            
            // Check if the student has access to this course (either directly enrolled or in same group)
            // First get the group_id for this course
            $stmt = $pdo->prepare("SELECT group_id FROM courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch();
            
            if (!$course) {
                jsonResponse(false, [], 'Course not found');
            }
            
            // Check if student is enrolled in any course in the same group
            $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                                   FROM enrollments e 
                                   JOIN courses c ON e.course_id = c.course_id 
                                   WHERE e.student_id = ? AND c.group_id = ?");
            $stmt->execute([$_SESSION['user_id'], $course['group_id']]);
            $enrollment = $stmt->fetch();
            
            // If student has no enrollments in this group, check if they have any enrollments at all
            if ($enrollment['count'] == 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $any_enrollment = $stmt->fetch();
                
                // If student has no enrollments at all, allow access (fallback for new students)
                if ($any_enrollment['count'] == 0) {
                    // Student has no enrollments, check if they exist in the system
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'student'");
                    $stmt->execute([$_SESSION['user_id']]);
                    if (!$stmt->fetch()) {
                        jsonResponse(false, [], 'Student not found');
                    }
                    // Allow access for students with no enrollments (they might be new)
                } else {
                    jsonResponse(false, [], 'You do not have access to this course');
                }
            }
            
            // Check if session record exists, if not create it
            $stmt = $pdo->prepare("SELECT session_id FROM attendance_sessions WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $session_exists = $stmt->fetch();
            
            if (!$session_exists) {
                // Create a temporary session record
                $session_date = date('Y-m-d', strtotime("+$session_number week"));
                $stmt = $pdo->prepare("INSERT INTO attendance_sessions 
                                       (session_id, course_id, session_number, session_date, session_time, created_by) 
                                       VALUES (?, ?, ?, ?, '09:00:00', ?)");
                $stmt->execute([$session_id, $course_id, $session_number, $session_date, $_SESSION['user_id']]);
            }
            
            $file_path = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload($_FILES['file']);
                if ($uploadResult['success']) {
                    $file_path = $uploadResult['file_path'];
                } else {
                    jsonResponse(false, [], $uploadResult['error']);
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO justification_requests 
                                   (student_id, session_id, reason, file_path) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $session_id, $reason, $file_path]);
            
            $request_id = $pdo->lastInsertId();
            jsonResponse(true, ['request_id' => $request_id], 'Justification submitted successfully');
            break;
            
        case 'get_student_requests':
            if (!hasRole('student')) {
                jsonResponse(false, [], 'Access denied');
            }
            
            // Get requests with session information
            $stmt = $pdo->prepare("SELECT j.*, 
                                   COALESCE(s.session_number, MOD(j.session_id, 100)) as session_number,
                                   COALESCE(s.session_date, DATE_ADD(NOW(), INTERVAL MOD(j.session_id, 100) WEEK)) as session_date,
                                   COALESCE(c.course_name, 'Unknown Course') as course_name 
                                   FROM justification_requests j
                                   LEFT JOIN attendance_sessions s ON j.session_id = s.session_id
                                   LEFT JOIN courses c ON (s.course_id = c.course_id OR FLOOR(j.session_id / 100) = c.course_id)
                                   WHERE j.student_id = ?
                                   ORDER BY j.submitted_at DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $requests = $stmt->fetchAll();
            
            jsonResponse(true, $requests, 'Requests retrieved');
            break;
            
        case 'get_pending':
            requireRole('administrator');
            
            // Get pending requests with session and user information
            $stmt = $pdo->prepare("SELECT j.request_id, j.student_id, j.session_id, j.reason, j.file_path, j.status, j.reviewed_by, j.reviewed_at, j.review_notes, j.submitted_at,
                                   u.student_id, u.first_name, u.last_name, 
                                   COALESCE(s.session_number, MOD(j.session_id, 100)) as session_number,
                                   COALESCE(s.session_date, DATE_ADD(NOW(), INTERVAL MOD(j.session_id, 100) WEEK)) as session_date,
                                   COALESCE(c.course_name, 'Unknown Course') as course_name 
                                   FROM justification_requests j
                                   JOIN users u ON j.student_id = u.user_id
                                   LEFT JOIN attendance_sessions s ON j.session_id = s.session_id
                                   LEFT JOIN courses c ON (s.course_id = c.course_id OR FLOOR(j.session_id / 100) = c.course_id)
                                   WHERE j.status = 'pending'
                                   ORDER BY j.submitted_at DESC");
            $stmt->execute();
            $requests = $stmt->fetchAll();
            
            jsonResponse(true, $requests, 'Pending requests retrieved');
            break;
            
        case 'review':
            requireRole('administrator');
            
            $request_id = intval($_POST['request_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? '');
            $review_notes = sanitize($_POST['review_notes'] ?? '');
            
            if ($request_id <= 0 || !in_array($status, ['approved', 'rejected'])) {
                jsonResponse(false, [], 'Invalid request or status');
            }
            
            $stmt = $pdo->prepare("UPDATE justification_requests 
                                   SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
                                   WHERE request_id = ?");
            $stmt->execute([$status, $_SESSION['user_id'], $review_notes, $request_id]);
            
            // If approved, update attendance record to excused
            if ($status === 'approved') {
                $stmt = $pdo->prepare("SELECT session_id, student_id FROM justification_requests WHERE request_id = ?");
                $stmt->execute([$request_id]);
                $request = $stmt->fetch();
                
                if ($request) {
                    // Check if session record exists, if not create it
                    $session_id = $request['session_id'];
                    $stmt = $pdo->prepare("SELECT session_id FROM attendance_sessions WHERE session_id = ?");
                    $stmt->execute([$session_id]);
                    $session_exists = $stmt->fetch();
                    
                    if (!$session_exists) {
                        // Create a temporary session record
                        $course_id = intval($session_id / 100);
                        $session_number = $session_id % 100;
                        $session_date = date('Y-m-d', strtotime("+$session_number week"));
                        $stmt = $pdo->prepare("INSERT INTO attendance_sessions 
                                               (session_id, course_id, session_number, session_date, session_time, created_by) 
                                               VALUES (?, ?, ?, ?, '09:00:00', ?)");
                        $stmt->execute([$session_id, $course_id, $session_number, $session_date, $_SESSION['user_id']]);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE attendance_records SET status = 'excused' 
                                           WHERE session_id = ? AND student_id = ?");
                    $stmt->execute([$request['session_id'], $request['student_id']]);
                }
            }
            
            jsonResponse(true, [], 'Request reviewed successfully');
            break;
            
        default:
            jsonResponse(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Justification API error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}

?>

