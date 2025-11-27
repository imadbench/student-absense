<?php
require_once __DIR__ . '/../../backend/includes/db_connect.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

if (!isLoggedIn() || !hasRole('professor')) {
    echo "Access denied. Please log in as professor.";
    exit;
}

try {
    $pdo = getConnection();
    
    // Get course ID from URL parameter or default to 1
    $course_id = intval($_GET['course_id'] ?? 1);
    
    echo "<h2>Enrollments for Course ID: " . htmlspecialchars($course_id) . "</h2>";
    
    // Check course exists
    $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        echo "<p>Course not found.</p>";
        exit;
    }
    
    echo "<p>Course Name: " . htmlspecialchars($course['course_name']) . "</p>";
    
    // Get all enrollments for this course
    $stmt = $pdo->prepare("SELECT e.enrollment_id, u.user_id, u.student_id, u.first_name, u.last_name, u.email
                           FROM enrollments e
                           JOIN users u ON e.student_id = u.user_id
                           WHERE e.course_id = ? AND u.role = 'student'
                           ORDER BY u.last_name, u.first_name");
    $stmt->execute([$course_id]);
    $enrollments = $stmt->fetchAll();
    
    echo "<h3>Enrolled Students (" . count($enrollments) . " found):</h3>";
    
    if (empty($enrollments)) {
        echo "<p>No students enrolled in this course.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Enrollment ID</th><th>User ID</th><th>Student ID</th><th>Name</th><th>Email</th></tr>";
        
        foreach ($enrollments as $enrollment) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($enrollment['enrollment_id']) . "</td>";
            echo "<td>" . htmlspecialchars($enrollment['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($enrollment['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($enrollment['email']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Also check all users with role 'student'
    echo "<h3>All Students in System:</h3>";
    $stmt = $pdo->prepare("SELECT user_id, student_id, first_name, last_name, email FROM users WHERE role = 'student' ORDER BY last_name, first_name");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>User ID</th><th>Student ID</th><th>Name</th><th>Email</th></tr>";
    
    foreach ($students as $student) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($student['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($student['email']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>