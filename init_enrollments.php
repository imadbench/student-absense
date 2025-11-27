<?php
/**
 * Initialize sample enrollments for testing
 * This script creates sample enrollments for students in courses
 */

require_once 'backend/includes/db_connect.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Initializing Sample Enrollments</h2>";
    
    // Get all students
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'student'");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        echo "<p>No students found. Please create some students first.</p>";
        exit;
    }
    
    // Get all courses
    $stmt = $pdo->prepare("SELECT course_id FROM courses");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($courses)) {
        echo "<p>No courses found. Please create some courses first.</p>";
        exit;
    }
    
    echo "<p>Found " . count($students) . " students and " . count($courses) . " courses.</p>";
    
    // Enroll each student in each course (for testing purposes)
    $enrollmentCount = 0;
    foreach ($students as $student) {
        foreach ($courses as $course) {
            // Check if enrollment already exists
            $stmt = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student['user_id'], $course['course_id']]);
            
            if (!$stmt->fetch()) {
                // Insert new enrollment
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                $stmt->execute([$student['user_id'], $course['course_id']]);
                $enrollmentCount++;
            }
        }
    }
    
    echo "<p>Successfully created $enrollmentCount new enrollments.</p>";
    echo "<p>Students should now see courses in their dashboard.</p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>