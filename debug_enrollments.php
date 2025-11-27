<?php
require_once 'backend/includes/db_connect.php';

try {
    $pdo = getConnection();
    
    // Check number of students
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role='student'");
    $stmt->execute();
    $studentCount = $stmt->fetch()['count'];
    
    // Check number of courses
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses");
    $stmt->execute();
    $courseCount = $stmt->fetch()['count'];
    
    // Check number of enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments");
    $stmt->execute();
    $enrollmentCount = $stmt->fetch()['count'];
    
    // Get sample students
    $stmt = $pdo->prepare("SELECT user_id, username, first_name, last_name FROM users WHERE role='student' LIMIT 5");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sample courses
    $stmt = $pdo->prepare("SELECT course_id, course_name, course_code FROM courses LIMIT 5");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sample enrollments
    $stmt = $pdo->prepare("SELECT e.enrollment_id, u.username, c.course_name 
                          FROM enrollments e 
                          JOIN users u ON e.student_id = u.user_id 
                          JOIN courses c ON e.course_id = c.course_id 
                          LIMIT 10");
    $stmt->execute();
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Database Debug Information</h2>";
    echo "<p><strong>Students:</strong> $studentCount</p>";
    echo "<p><strong>Courses:</strong> $courseCount</p>";
    echo "<p><strong>Enrollments:</strong> $enrollmentCount</p>";
    
    echo "<h3>Sample Students:</h3>";
    echo "<pre>" . print_r($students, true) . "</pre>";
    
    echo "<h3>Sample Courses:</h3>";
    echo "<pre>" . print_r($courses, true) . "</pre>";
    
    echo "<h3>Sample Enrollments:</h3>";
    if (empty($enrollments)) {
        echo "<p>No enrollments found</p>";
    } else {
        echo "<pre>" . print_r($enrollments, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>