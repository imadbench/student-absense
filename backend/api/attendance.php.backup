<?php
/**
 * Attendance API Endpoint
 */

// Ensure no output before JSON responses
ob_start();

// Clear any previous output
if (ob_get_level()) {
    ob_clean();
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Ensure JSON header is set
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getConnection();
    
    switch ($action) {
        case 'create_session':
            // Session creation completely disabled
            jsonResponse(false, [], 'Session creation is disabled. Each course has 6 fixed sessions.');
            break;
            
        case 'get_sessions':
            $course_id = intval($_GET['course_id'] ?? 0);
            
            if ($course_id <= 0) {
                jsonResponse(false, [], 'Invalid course ID');
            }
            
            // Return 6 fixed virtual sessions - no database storage
            $sessions = [];
            for ($i = 1; $i <= 6; $i++) {
                $sessions[] = [
                    'session_id' => $course_id * 100 + $i,
                    'course_id' => $course_id,
                    'session_number' => $i,
                    'session_date' => date('Y-m-d', strtotime("+$i week")),
                    'session_time' => '09:00:00',
                    'status' => 'open',
                    'created_by' => $_SESSION['user_id'] ?? 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'notes' => "Session $i"
                ];
            }
            
            jsonResponse(true, $sessions, 'Fixed sessions retrieved');
            break;
            
        case 'get_session_details':
            $session_id = intval($_GET['session_id'] ?? 0);
            
            if ($session_id <= 0) {
                jsonResponse(false, [], 'Invalid session ID');
            }
            
            // Extract course_id and session_number from virtual session_id
            $course_id = intval($session_id / 100);
            $session_number = $session_id % 100;
            
            if ($session_number < 1 || $session_number > 6) {
                jsonResponse(false, [], 'Invalid session number');
            }
            
            // Get course info
            $stmt = $pdo->prepare("SELECT course_name, course_code FROM courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch();
            
            if (!$course) {
                jsonResponse(false, [], 'Course not found');
            }
            
            // Create virtual session object
            $session = [
                'session_id' => $session_id,
                'course_id' => $course_id,
                'session_number' => $session_number,
                'session_date' => date('Y-m-d', strtotime("+$session_number week")),
                'session_time' => '09:00:00',
                'status' => 'open',
                'course_name' => $course['course_name'],
                'course_code' => $course['course_code'],
                'notes' => "Session $session_number"
            ];
            
            jsonResponse(true, $session, 'Session details retrieved');
            break;
            
        case 'mark_attendance':
            requireRole('professor');
            
            $session_id = intval($_POST['session_id'] ?? 0);
            $student_id = intval($_POST['student_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'absent');
            
            if ($session_id <= 0 || $student_id <= 0) {
                jsonResponse(false, [], 'Invalid session or student');
            }
            
            // Check if record exists
            $stmt = $pdo->prepare("SELECT record_id FROM attendance_records 
                                   WHERE session_id = ? AND student_id = ?");
            $stmt->execute([$session_id, $student_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE attendance_records SET status = ?, marked_at = NOW() 
                                       WHERE record_id = ?");
                $stmt->execute([$status, $existing['record_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id, status) 
                                       VALUES (?, ?, ?)");
                $stmt->execute([$session_id, $student_id, $status]);
            }
            
            jsonResponse(true, [], 'Attendance marked successfully');
            break;
            
        case 'get_attendance':
            $session_id = intval($_GET['session_id'] ?? 0);
            
            if ($session_id <= 0) {
                jsonResponse(false, [], 'Invalid session ID');
            }
            
            // Extract course_id and session_number from virtual session_id
            $course_id = intval($session_id / 100);
            $session_number = $session_id % 100;
            
            if ($session_number < 1 || $session_number > 6) {
                jsonResponse(false, [], 'Invalid session number');
            }
            
            // Get course info
            $stmt = $pdo->prepare("SELECT course_name, group_id FROM courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch();
            
            if (!$course) {
                jsonResponse(false, [], 'Course not found');
            }
            
            // Create virtual session object
            $session = [
                'session_id' => $session_id,
                'course_id' => $course_id,
                'session_number' => $session_number,
                'session_date' => date('Y-m-d', strtotime("+$session_number week")),
                'course_name' => $course['course_name'],
                'group_id' => $course['group_id']
            ];
            
            // Get all students who should be in this course
            $students = [];
            
            // Method 1: Get directly enrolled students
            $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name 
                                   FROM enrollments e
                                   JOIN users u ON e.student_id = u.user_id
                                   WHERE e.course_id = ? AND u.role = 'student'
                                   ORDER BY u.last_name, u.first_name");
            $stmt->execute([$course_id]);
            $students = $stmt->fetchAll();
            
            // Method 2: If no direct enrollments, get students from same group
            if (empty($students) && !empty($session['group_id'])) {
                $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name 
                                       FROM users u
                                       WHERE u.role = 'student' 
                                       AND EXISTS (
                                           SELECT 1 FROM enrollments e 
                                           JOIN courses c ON e.course_id = c.course_id 
                                           WHERE c.group_id = ? AND e.student_id = u.user_id
                                       )
                                       ORDER BY u.last_name, u.first_name");
                $stmt->execute([$session['group_id']]);
                $students = $stmt->fetchAll();
            }
            
            // Method 3: Last resort - get all student users (only if system is empty)
            if (empty($students)) {
                error_log("No students found for course_id: $course_id, group_id: " . ($session['group_id'] ?? 'null') . " - Using all students as fallback");
                $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name 
                                       FROM users u
                                       WHERE u.role = 'student'
                                       ORDER BY u.last_name, u.first_name");
                $stmt->execute();
                $students = $stmt->fetchAll();
            }
            
            if (empty($students)) {
                jsonResponse(false, [], 'No students found in the system');
            }
            
            // Get attendance records for THIS SPECIFIC SESSION only
            $stmt = $pdo->prepare("SELECT student_id, status, marked_at
                                   FROM attendance_records
                                   WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $attendance_records = $stmt->fetchAll();
            
            // Create attendance lookup for this session
            $attendance_lookup = [];
            foreach ($attendance_records as $record) {
                $attendance_lookup[$record['student_id']] = $record;
            }
            
            // Get participation records for THIS SPECIFIC SESSION only
            $stmt = $pdo->prepare("SELECT student_id, participation_type, marked_at
                                   FROM participation_records
                                   WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $participation_records = $stmt->fetchAll();
            
            // Create participation lookup for this session
            $participation_lookup = [];
            foreach ($participation_records as $record) {
                if (!isset($participation_lookup[$record['student_id']])) {
                    $participation_lookup[$record['student_id']] = [];
                }
                $participation_lookup[$record['student_id']][] = $record;
            }
            
            // Build complete student data for THIS SESSION
            $records = [];
            foreach ($students as $student) {
                $attendance = $attendance_lookup[$student['user_id']] ?? ['status' => 'absent', 'marked_at' => null];
                $participations = $participation_lookup[$student['user_id']] ?? [];
                
                $records[] = [
                    'user_id' => $student['user_id'],
                    'student_id' => $student['student_id'],
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'status' => $attendance['status'],
                    'marked_at' => $attendance['marked_at'],
                    'participations' => $participations
                ];
            }
            
            jsonResponse(true, [
                'session_info' => [
                    'session_id' => $session['session_id'],
                    'session_number' => $session['session_number'],
                    'session_date' => $session['session_date'],
                    'course_name' => $session['course_name']
                ],
                'students' => $records
            ], 'Attendance retrieved for this session only');
            break;
            
        case 'close_session':
            requireRole('professor');
            
            $session_id = intval($_POST['session_id'] ?? 0);
            
            if ($session_id <= 0) {
                jsonResponse(false, [], 'Invalid session ID');
            }
            
            $stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'closed', closed_at = NOW() 
                                   WHERE session_id = ? AND created_by = ?");
            $stmt->execute([$session_id, $_SESSION['user_id']]);
            
            jsonResponse(true, [], 'Session closed successfully');
            break;
            
        case 'get_summary':
            $course_id = intval($_GET['course_id'] ?? 0);
            $group_id = intval($_GET['group_id'] ?? 0);
            
            if ($course_id <= 0) {
                jsonResponse(false, [], 'Invalid course ID');
            }
            
            // Get all students who should be in this course
            $stmt = $pdo->prepare("SELECT c.group_id FROM courses c WHERE c.course_id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch();
            
            if ($course && !empty($course['group_id'])) {
                // First try to get enrolled students
                $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name 
                                       FROM enrollments e
                                       JOIN users u ON e.student_id = u.user_id
                                       WHERE e.course_id = ?
                                       ORDER BY u.last_name, u.first_name");
                $stmt->execute([$course_id]);
                $students = $stmt->fetchAll();
                
                // If no enrolled students, get all students from the same group
                if (empty($students)) {
                    $stmt = $pdo->prepare("SELECT DISTINCT u.user_id, u.student_id, u.first_name, u.last_name 
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
                }
            } else {
                $students = [];
            }
            
            // Get all sessions for this course
            $stmt = $pdo->prepare("SELECT session_id, session_number, session_date 
                                   FROM attendance_sessions 
                                   WHERE course_id = ? AND status = 'closed'
                                   ORDER BY session_number");
            $stmt->execute([$course_id]);
            $sessions = $stmt->fetchAll();
            
            // Get attendance records
            $summary = [];
            foreach ($students as $student) {
                $studentSummary = [
                    'student_id' => $student['student_id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'sessions' => [],
                    'total_present' => 0,
                    'total_absent' => 0,
                    'total_late' => 0,
                    'total_excused' => 0
                ];
                
                foreach ($sessions as $session) {
                    $stmt = $pdo->prepare("SELECT status FROM attendance_records 
                                           WHERE session_id = ? AND student_id = ?");
                    $stmt->execute([$session['session_id'], $student['user_id']]);
                    $record = $stmt->fetch();
                    
                    $status = $record ? $record['status'] : 'absent';
                    $studentSummary['sessions'][] = [
                        'session_number' => $session['session_number'],
                        'status' => $status
                    ];
                    
                    if ($status === 'present') $studentSummary['total_present']++;
                    elseif ($status === 'absent') $studentSummary['total_absent']++;
                    elseif ($status === 'late') $studentSummary['total_late']++;
                    elseif ($status === 'excused') $studentSummary['total_excused']++;
                }
                
                $summary[] = $studentSummary;
            }
            
            jsonResponse(true, [
                'summary' => $summary,
                'sessions' => $sessions
            ], 'Summary retrieved');
            break;
            
        case 'save_attendance_participation':
            requireRole('professor');
            
            $session_id = intval($_POST['session_id'] ?? 0);
            
            if ($session_id <= 0) {
                jsonResponse(false, [], 'Invalid session ID');
            }
            
            // Verify session exists and professor has access
            $stmt = $pdo->prepare("SELECT s.course_id, c.professor_id 
                                   FROM attendance_sessions s
                                   JOIN courses c ON s.course_id = c.course_id
                                   WHERE s.session_id = ? AND c.professor_id = ?");
            $stmt->execute([$session_id, $_SESSION['user_id']]);
            $session = $stmt->fetch();
            
            if (!$session) {
                jsonResponse(false, [], 'Session not found or access denied');
            }
            
            $course_id = $session['course_id'];
            
            // Handle JSON data
            $attendance_data = [];
            $participation_data = [];
            
            if (isset($_POST['attendance'])) {
                $attendance_data = json_decode($_POST['attendance'], true) ?: [];
            }
            
            if (isset($_POST['participation'])) {
                $participation_data = json_decode($_POST['participation'], true) ?: [];
            }
            
            if (empty($attendance_data) && empty($participation_data)) {
                jsonResponse(false, [], 'No data to save');
            }
            
            try {
                $pdo->beginTransaction();
                
                // Get all students who should be in this course
                $stmt = $pdo->prepare("SELECT c.group_id FROM courses c WHERE c.course_id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch();
                
                $valid_students = [];
                if ($course && !empty($course['group_id'])) {
                    // Get enrolled students first
                    $stmt = $pdo->prepare("SELECT u.user_id, u.student_id 
                                           FROM enrollments e
                                           JOIN users u ON e.student_id = u.user_id
                                           WHERE e.course_id = ?");
                    $stmt->execute([$course_id]);
                    $enrolled = $stmt->fetchAll();
                    
                    foreach ($enrolled as $student) {
                        $valid_students[$student['student_id']] = $student['user_id'];
                    }
                    
                    // If no enrolled students, get from same group
                    if (empty($valid_students)) {
                        $stmt = $pdo->prepare("SELECT DISTINCT u.user_id, u.student_id 
                                               FROM users u
                                               WHERE u.role = 'student' 
                                               AND u.user_id IN (
                                                   SELECT e.student_id 
                                                   FROM enrollments e 
                                                   JOIN courses c ON e.course_id = c.course_id 
                                                   WHERE c.group_id = ?
                                               )");
                        $stmt->execute([$course['group_id']]);
                        $group_students = $stmt->fetchAll();
                        
                        foreach ($group_students as $student) {
                            $valid_students[$student['student_id']] = $student['user_id'];
                        }
                    }
                }
                
                // Fallback to all students if still empty
                if (empty($valid_students)) {
                    $stmt = $pdo->prepare("SELECT u.user_id, u.student_id 
                                           FROM users u
                                           WHERE u.role = 'student'");
                    $stmt->execute();
                    $all_students = $stmt->fetchAll();
                    
                    foreach ($all_students as $student) {
                        $valid_students[$student['student_id']] = $student['user_id'];
                    }
                }
                
                $saved_attendance = 0;
                $saved_participations = 0;
                
                // STEP 1: Clear ALL existing data for THIS SESSION ONLY
                // This ensures complete separation between sessions
                
                // Clear attendance records for this session only
                $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE session_id = ?");
                $stmt->execute([$session_id]);
                
                // Clear participation records for this session only
                $stmt = $pdo->prepare("DELETE FROM participation_records WHERE session_id = ?");
                $stmt->execute([$session_id]);
                
                // STEP 2: Insert new attendance data for THIS SESSION ONLY
                foreach ($attendance_data as $student_id => $status) {
                    $student_id = intval($student_id);
                    $status = sanitize($status);
                    
                    if ($student_id <= 0 || !isset($valid_students[$student_id])) {
                        continue;
                    }
                    
                    $user_id = $valid_students[$student_id];
                    
                    // Insert new attendance record for THIS SESSION
                    $stmt = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id, status, marked_at) 
                                           VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$session_id, $user_id, $status]);
                    $saved_attendance++;
                }
                
                // STEP 3: Insert new participation data for THIS SESSION ONLY
                foreach ($participation_data as $student_id => $participations) {
                    $student_id = intval($student_id);
                    
                    if ($student_id <= 0 || !isset($valid_students[$student_id])) {
                        continue;
                    }
                    
                    $user_id = $valid_students[$student_id];
                    
                    // Insert new participation records for THIS SESSION ONLY
                    if (is_array($participations)) {
                        foreach ($participations as $participation_type) {
                            $participation_type = sanitize($participation_type);
                            if (!empty($participation_type)) {
                                $stmt = $pdo->prepare("INSERT INTO participation_records 
                                                       (session_id, student_id, participation_type, marked_at) 
                                                       VALUES (?, ?, ?, NOW())");
                                $stmt->execute([$session_id, $user_id, $participation_type]);
                                $saved_participations++;
                            }
                        }
                    }
                }
                
                $pdo->commit();
                
                // Get updated statistics for THIS SESSION ONLY
                $stmt = $pdo->prepare("SELECT 
                                           COUNT(*) as total,
                                           SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                                           SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                                           SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                                           SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
                                           FROM attendance_records 
                                           WHERE session_id = ?");
                $stmt->execute([$session_id]);
                $attendance_stats = $stmt->fetch();
                
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) as participation_count
                                       FROM participation_records 
                                       WHERE session_id = ?");
                $stmt->execute([$session_id]);
                $participation_stats = $stmt->fetch();
                
                jsonResponse(true, [
                    'session_id' => $session_id,
                    'saved_attendance_records' => $saved_attendance,
                    'saved_participation_records' => $saved_participations,
                    'session_statistics' => [
                        'total_students' => $attendance_stats['total'],
                        'present' => $attendance_stats['present'],
                        'absent' => $attendance_stats['absent'],
                        'late' => $attendance_stats['late'],
                        'excused' => $attendance_stats['excused'],
                        'participation_count' => $participation_stats['participation_count']
                    ],
                    'message' => 'Data saved successfully for this session only - Other sessions remain unchanged'
                ], 'Session attendance and participation saved successfully - Data is completely separate for this session');
                
            } catch (Exception $e) {
                $pdo->rollback();
            
            if ($course_id <= 0) {
                error_log("Invalid course ID: $course_id\n", 3, __DIR__ . "/../logs/api_errors.log");
                jsonResponse(false, [], 'Invalid course ID');
            }
            
            // Create virtual sessions for this course (6 sessions total)
            $sessions = [];
            for ($i = 1; $i <= 6; $i++) {
                $sessions[] = [
                    'session_id' => $course_id * 100 + $i,
                    'session_number' => $i,
                    'session_date' => date('Y-m-d', strtotime("+$i week"))
                ];
            }
            
            error_log("Created virtual sessions for course_id: $course_id\n", 3, __DIR__ . "/../logs/api_errors.log");
            
            // Get all students who should be in this course
            $stmt = $pdo->prepare("SELECT c.group_id FROM courses c WHERE c.course_id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch();
            
            if ($course && !empty($course['group_id'])) {
                // First try to get enrolled students
                $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name 
                                       FROM enrollments e
                                       JOIN users u ON e.student_id = u.user_id
                                       WHERE e.course_id = ?
                                       ORDER BY u.last_name, u.first_name");
                $stmt->execute([$course_id]);
                $students = $stmt->fetchAll();
                
                // If no enrolled students, get all students from the same group
                if (empty($students)) {
                    $stmt = $pdo->prepare("SELECT DISTINCT u.user_id, u.student_id, u.first_name, u.last_name 
                                           FROM users u
                                           WHERE u.role = 'student' AND u.user_id IN (
                                               SELECT e.student_id FROM enrollments e 
                                               JOIN courses c ON e.course_id = c.course_id 
                                               WHERE c.group_id = ?
                                           )
                                           ORDER BY u.last_name, u.first_name");
                    $stmt->execute([$course['group_id']]);
                    $students = $stmt->fetchAll();
                }
            } else {
                $students = [];
            }
            
            error_log("Found " . count($students) . " students for course_id: $course_id\n", 3, __DIR__ . "/../logs/api_errors.log");
            
            if (empty($students)) {
                error_log("No enrolled students found for course_id: $course_id, group_id: " . ($course['group_id'] ?? 'null') . " - Using fallback to all students");
                
                // Get all students as fallback
                $stmt = $pdo->prepare("SELECT u.user_id, u.student_id, u.first_name, u.last_name 
                                       FROM users u
                                       WHERE u.role = 'student'
                                       ORDER BY u.last_name, u.first_name");
                $stmt->execute();
                $students = $stmt->fetchAll();
                
                if (!empty($students)) {
                    error_log("Found " . count($students) . " students as fallback for course_id: $course_id");
                } else {
                    jsonResponse(false, [], 'No students found in the system');
                }
            }
            
            // Prepare the result structure
            $result = [];
            
            // For each student, get their attendance and participation data for all sessions
            foreach ($students as $student) {
                $studentData = [
                    'student_id' => $student['student_id'],
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'sessions' => []
                ];
                
                // For each session, get attendance and participation data
                foreach ($sessions as $session) {
                    $session_id = $session['session_id'];
                    
                    // Get attendance record for this student in this session
                    $stmt = $pdo->prepare("SELECT status FROM attendance_records 
                                           WHERE session_id = ? AND student_id = ?");
                    $stmt->execute([$session_id, $student['user_id']]);
                    $attendance = $stmt->fetch();
                    
                    // Get participation records for this student in this session
                    $stmt = $pdo->prepare("SELECT participation_type 
                                           FROM participation_records 
                                           WHERE session_id = ? AND student_id = ?");
                    $stmt->execute([$session_id, $student['user_id']]);
                    $participations = $stmt->fetchAll();
                    
                    $studentData['sessions'][] = [
                        'session_id' => $session_id,
                        'session_number' => $session['session_number'],
                        'status' => $attendance ? $attendance['status'] : 'absent',
                        'participations' => $participations
                    ];
                }
                
                $result[] = $studentData;
            }
            
            error_log("Returning data for " . count($result) . " students\n", 3, __DIR__ . "/../logs/api_errors.log");
            
            jsonResponse(true, [
                'students' => $result,
                'sessions' => $sessions
            ], 'All sessions attendance retrieved');
            break;
            
        default:
            jsonResponse(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Attendance API error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}

?>