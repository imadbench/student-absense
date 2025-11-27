<?php
/**
 * Statistics API Endpoint
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('administrator');
header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $total_students = $stmt->fetch()['total'];
    
    // Total professors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'professor'");
    $total_professors = $stmt->fetch()['total'];
    
    // Total courses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
    $total_courses = $stmt->fetch()['total'];
    
    // Total sessions
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance_sessions");
    $total_sessions = $stmt->fetch()['total'];
    
    // Attendance statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_records,
        COALESCE(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END), 0) as present,
        COALESCE(SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END), 0) as absent,
        COALESCE(SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END), 0) as late,
        COALESCE(SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END), 0) as excused
        FROM attendance_records");
    $attendance_stats = $stmt->fetch();
    
    // Ensure all values are set
    if (!$attendance_stats) {
        $attendance_stats = [
            'total_records' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0
        ];
    }
    
    // Attendance by month
    $stmt = $pdo->query("SELECT 
        DATE_FORMAT(session_date, '%Y-%m') as month,
        COUNT(DISTINCT s.session_id) as sessions,
        COUNT(ar.record_id) as records,
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present
        FROM attendance_sessions s
        LEFT JOIN attendance_records ar ON s.session_id = ar.session_id
        WHERE s.session_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month");
    $monthly_stats = $stmt->fetchAll();
    
    // Top courses by attendance
    $stmt = $pdo->query("SELECT 
        c.course_name,
        COUNT(DISTINCT s.session_id) as sessions,
        COUNT(ar.record_id) as total_records,
        COALESCE(SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END), 0) as present,
        CASE 
            WHEN COUNT(ar.record_id) > 0 
            THEN ROUND(COALESCE(SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END), 0) * 100.0 / COUNT(ar.record_id), 2)
            ELSE 0 
        END as attendance_rate
        FROM courses c
        LEFT JOIN attendance_sessions s ON c.course_id = s.course_id
        LEFT JOIN attendance_records ar ON s.session_id = ar.session_id
        GROUP BY c.course_id, c.course_name
        HAVING sessions > 0
        ORDER BY attendance_rate DESC, sessions DESC
        LIMIT 10");
    $top_courses = $stmt->fetchAll();
    
    // If no courses with sessions, return empty array
    if (!$top_courses) {
        $top_courses = [];
    }
    
    // Pending justifications
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM justification_requests WHERE status = 'pending'");
    $pending_justifications = $stmt->fetch()['total'];
    
    jsonResponse(true, [
        'overview' => [
            'total_students' => (int)$total_students,
            'total_professors' => (int)$total_professors,
            'total_courses' => (int)$total_courses,
            'total_sessions' => (int)$total_sessions,
            'pending_justifications' => (int)$pending_justifications
        ],
        'attendance' => $attendance_stats,
        'monthly' => $monthly_stats,
        'top_courses' => $top_courses
    ], 'Statistics retrieved');
    
} catch (Exception $e) {
    error_log("Statistics API error: " . $e->getMessage(), 3, __DIR__ . "/../logs/api_errors.log");
    jsonResponse(false, [], 'An error occurred: ' . $e->getMessage());
}

?>

