<?php
/**
 * Simple script to check if database has necessary data
 */

require_once __DIR__ . '/../../backend/includes/db_connect.php';

try {
    $pdo = getConnection();
    
    // Check users
    $stmt = $pdo->prepare("SELECT user_id, username, first_name, last_name, role FROM users ORDER BY user_id LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>Users in Database:</h2>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>ID</th><th>Username</th><th>First Name</th><th>Last Name</th><th>Role</th></tr>\n";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Check courses
    $stmt = $pdo->prepare("SELECT course_id, course_name, course_code FROM courses ORDER BY course_id");
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    echo "<h2>Courses in Database:</h2>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Code</th></tr>\n";
    foreach ($courses as $course) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($course['course_id']) . "</td>";
        echo "<td>" . htmlspecialchars($course['course_name']) . "</td>";
        echo "<td>" . htmlspecialchars($course['course_code']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Check enrollments
    $stmt = $pdo->prepare("SELECT e.enrollment_id, e.course_id, e.student_id, u.first_name, u.last_name, c.course_name 
                           FROM enrollments e 
                           JOIN users u ON e.student_id = u.user_id 
                           JOIN courses c ON e.course_id = c.course_id 
                           ORDER BY e.course_id, u.last_name, u.first_name");
    $stmt->execute();
    $enrollments = $stmt->fetchAll();
    
    echo "<h2>Enrollments in Database:</h2>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>Enrollment ID</th><th>Course ID</th><th>Student ID</th><th>Student Name</th><th>Course Name</th></tr>\n";
    foreach ($enrollments as $enrollment) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($enrollment['enrollment_id']) . "</td>";
        echo "<td>" . htmlspecialchars($enrollment['course_id']) . "</td>";
        echo "<td>" . htmlspecialchars($enrollment['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($enrollment['first_name'] . " " . $enrollment['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($enrollment['course_name']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>