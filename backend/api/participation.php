<?php
/**
 * Participation API Endpoint
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
        case 'mark':
            requireRole('professor');
            
            $session_id = intval($_POST['session_id'] ?? 0);
            $student_id = intval($_POST['student_id'] ?? 0);
            $participation_type = sanitize($_POST['type'] ?? 'question');
            $notes = sanitize($_POST['notes'] ?? '');
            
            if ($session_id <= 0 || $student_id <= 0) {
                jsonResponse(false, [], 'Invalid session or student');
            }
            
            $stmt = $pdo->prepare("INSERT INTO participation_records 
                                   (session_id, student_id, participation_type, notes) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$session_id, $student_id, $participation_type, $notes]);
            
            $participation_id = $pdo->lastInsertId();
            jsonResponse(true, ['participation_id' => $participation_id], 'Participation recorded');
            break;
            
        case 'get_session_participation':
            $session_id = intval($_GET['session_id'] ?? 0);
            
            if ($session_id <= 0) {
                jsonResponse(false, [], 'Invalid session ID');
            }
            
            $stmt = $pdo->prepare("SELECT p.*, u.student_id, u.first_name, u.last_name 
                                   FROM participation_records p
                                   JOIN users u ON p.student_id = u.user_id
                                   WHERE p.session_id = ?
                                   ORDER BY p.marked_at DESC");
            $stmt->execute([$session_id]);
            $records = $stmt->fetchAll();
            
            jsonResponse(true, $records, 'Participation retrieved');
            break;
            
        case 'get_student_participation':
            $student_id = intval($_GET['student_id'] ?? 0);
            $course_id = intval($_GET['course_id'] ?? 0);
            
            if ($student_id <= 0 || $course_id <= 0) {
                jsonResponse(false, [], 'Invalid student or course ID');
            }
            
            $stmt = $pdo->prepare("SELECT p.*, s.session_number, s.session_date 
                                   FROM participation_records p
                                   JOIN attendance_sessions s ON p.session_id = s.session_id
                                   WHERE p.student_id = ? AND s.course_id = ?
                                   ORDER BY s.session_date DESC");
            $stmt->execute([$student_id, $course_id]);
            $records = $stmt->fetchAll();
            
            jsonResponse(true, $records, 'Participation retrieved');
            break;
            
        case 'delete':
            requireRole('professor');
            
            $participation_id = intval($_POST['participation_id'] ?? 0);
            
            if ($participation_id <= 0) {
                jsonResponse(false, [], 'Invalid participation ID');
            }
            
            $stmt = $pdo->prepare("DELETE FROM participation_records WHERE participation_id = ?");
            $stmt->execute([$participation_id]);
            
            jsonResponse(true, [], 'Participation record deleted');
            break;
            
        default:
            jsonResponse(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Participation API error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}

?>

