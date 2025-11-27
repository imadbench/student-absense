<?php
/**
 * Debug script to test database queries
 */

require_once __DIR__ . '/../../backend/includes/db_connect.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

// Ensure user is logged in as professor
requireRole('professor');

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    // Test 1: Check if we can connect to the database
    echo "Test 1: Database connection\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Total users in database: " . $result['count'] . "\n";
    
    // Test 2: Check students
    echo "\nTest 2: Students\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Total students: " . $result['count'] . "\n";
    
    // Test 3: Check courses
    echo "\nTest 3: Courses\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Total courses: " . $result['count'] . "\n";
    
    // Test 4: Check enrollments
    echo "\nTest 4: Enrollments\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Total enrollments: " . $result['count'] . "\n";
    
    // Test 5: Check a specific course
    echo "\nTest 5: Course ID 1 details\n";
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = 1");
    $stmt->execute();
    $course = $stmt->fetch();
    if ($course) {
        echo "Course found: " . $course['course_name'] . "\n";
        echo "Course code: " . $course['course_code'] . "\n";
        echo "Group ID: " . $course['group_id'] . "\n";
    } else {
        echo "No course with ID 1 found\n";
    }
    
    // Test 6: Check enrollments for course 1
    echo "\nTest 6: Enrollments for course 1\n";
    $stmt = $pdo->prepare("SELECT e.*, u.first_name, u.last_name, u.student_id 
                           FROM enrollments e 
                           JOIN users u ON e.student_id = u.user_id 
                           WHERE e.course_id = 1");
    $stmt->execute();
    $enrollments = $stmt->fetchAll();
    echo "Enrollments found: " . count($enrollments) . "\n";
    foreach ($enrollments as $enrollment) {
        echo "- Student: " . $enrollment['first_name'] . " " . $enrollment['last_name'] . " (" . $enrollment['student_id'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>