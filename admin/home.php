<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Administrator Dashboard</h1>
            <p>Manage students, courses, and view system statistics</p>
        </div>
        
        <div class="admin-cards">
            <a href="statistics.php" class="admin-card">
                <div class="card-icon"><i class="fas fa-chart-bar"></i></div>
                <h3>Statistics</h3>
                <p>View system-wide statistics and reports</p>
            </a>
            <a href="students.php" class="admin-card">
                <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                <h3>Student Management</h3>
                <p>Add, remove, and manage students</p>
            </a>
            <a href="courses.php" class="admin-card">
                <div class="card-icon"><i class="fas fa-book"></i></div>
                <h3>Course Management</h3>
                <p>Manage courses and enrollments</p>
            </a>
            <a href="enroll_students.php" class="admin-card">
                <div class="card-icon"><i class="fas fa-user-plus"></i></div>
                <h3>Enroll Students</h3>
                <p>Enroll students in courses</p>
            </a>
            <a href="justifications.php" class="admin-card">
                <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                <h3>Justification Review</h3>
                <p>Review and manage student justification requests</p>
            </a>
            <a href="professors.php" class="admin-card">
                <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3>Professor Management</h3>
                <p>Add and manage professors</p>
            </a>
        </div>
        
        <div class="quick-stats" id="quickStats">
            <div class="loading">Loading statistics...</div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script src="../assets/js/admin/home.js"></script>
</body>
</html>

