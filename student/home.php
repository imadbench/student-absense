<?php
require_once __DIR__ . '/../backend/includes/auth.php';
require_once __DIR__ . '/../backend/includes/db_connect.php';
requireRole('student');

// Load all courses as the main display
$courses = [];

try {
    $pdo = getConnection();
    
    // Get all courses
    $stmt = $pdo->prepare("SELECT c.*, g.group_name, g.group_code, u.first_name as prof_first_name, u.last_name as prof_last_name
                           FROM courses c
                           LEFT JOIN `groups` g ON c.group_id = g.group_id
                           LEFT JOIN users u ON c.professor_id = u.user_id
                           ORDER BY c.academic_year DESC, c.course_name");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Error loading courses: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Home - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Student Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!</p>
        </div>
        
        <div class="dashboard-overview">
            <div class="overview-card">
                <div class="overview-icon">📚</div>
                <div class="overview-content">
                    <h3>Available Courses</h3>
                    <p>There are <?php echo count($courses); ?> courses available</p>
                </div>
            </div>
        </div>
        
        <div class="section-container">
            <div class="section-header">
                <h2>All Available Courses</h2>
                <p>Contact your administrator to enroll in these courses</p>
            </div>
            
            <div class="courses-grid" id="coursesGrid">
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <h3><?php echo htmlspecialchars($course['course_name'] ?? 'Unknown Course'); ?></h3>
                                <span class="course-code"><?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="course-details">
                                <p><strong>Group:</strong> <?php echo htmlspecialchars($course['group_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($course['group_code'] ?? 'N/A'); ?>)</p>
                                <p><strong>Professor:</strong> <?php echo htmlspecialchars(($course['prof_first_name'] ?? '') . ' ' . ($course['prof_last_name'] ?? '')); ?></p>
                                <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($course['academic_year'] ?? 'N/A'); ?></p>
                                <p><strong>Semester:</strong> <?php echo htmlspecialchars($course['semester'] ?? 'N/A'); ?></p>
                                <?php if (!empty($course['course_day']) || !empty($course['course_time'])): ?>
                                    <p><strong>Schedule:</strong> 
                                        <?php 
                                        echo htmlspecialchars($course['course_day'] ?? ''); 
                                        if (!empty($course['course_time'])) {
                                            echo ' at ' . htmlspecialchars(date('g:i A', strtotime($course['course_time'])));
                                        }
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="course-footer">
                                <span class="status-badge status-pending">Not Enrolled</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📚</div>
                        <h3>No Courses Available</h3>
                        <p>No courses have been created yet. Please contact your administrator.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section-container">
            <div class="section-header">
                <h2>Attendance Management</h2>
                <p>View your attendance records and submit justifications</p>
            </div>
            
            <div class="admin-cards">
                <a class="admin-card" href="my_attendance.php">
                    <div class="card-icon">📋</div>
                    <h3>My Attendance</h3>
                    <p>View your attendance records across all courses</p>
                </a>
                
                <a class="admin-card" href="justification.php">
                    <div class="card-icon">📝</div>
                    <h3>My Justifications</h3>
                    <p>View your submitted justifications and their status</p>
                </a>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    
    <style>
        .course-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
    </style>
</body>
</html>