<?php
require_once __DIR__ . '/../backend/includes/auth.php';
require_once __DIR__ . '/../backend/includes/db_connect.php';
requireRole('professor');

// Load professor courses server-side
$courses = [];
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT c.*, g.group_name, g.group_code 
                           FROM courses c
                           LEFT JOIN `groups` g ON c.group_id = g.group_id
                           WHERE c.professor_id = ?
                           ORDER BY c.academic_year DESC, c.course_name");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error loading professor courses: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Home - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Professor Dashboard</h1>
            <p>Welcome back! Manage your courses and student attendance</p>
        </div>
        
        <div class="dashboard-overview">
            <div class="overview-card">
                <div class="overview-icon">📚</div>
                <div class="overview-content">
                    <h3>Your Courses</h3>
                    <p>You're teaching <?php echo count($courses); ?> courses this semester</p>
                </div>
            </div>
        </div>
        
        <div class="section-container">
            <div class="section-header">
                <h2>My Courses</h2>
                <p>Select a course to view and manage attendance sessions</p>
            </div>
            
            <div class="courses-grid" id="coursesGrid">
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <?php
                            $courseUrl = 'session/index.php?course_id=' . urlencode($course['course_id']);
                            $attendanceUrl = 'attendance/index.html?course_id=' . urlencode($course['course_id']);
                        ?>
                        <a class="course-card" href="<?php echo htmlspecialchars($courseUrl); ?>" target="_blank">
                            <div class="course-header">
                                <h3><?php echo htmlspecialchars($course['course_name'] ?? 'Unknown Course'); ?></h3>
                                <span class="course-code"><?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="course-details">
                                <p><strong>Group:</strong> <?php echo htmlspecialchars($course['group_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($course['group_code'] ?? 'N/A'); ?>)</p>
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
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📚</div>
                        <h3>No Courses Found</h3>
                        <p>Please contact your administrator to assign courses.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section-container">
            <div class="section-header">
                <h2>Attendance Management</h2>
                <p>Access the complete attendance management system</p>
            </div>
            
            <div class="admin-cards">
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <a class="admin-card" href="attendance/index.html?course_id=<?php echo urlencode($course['course_id']); ?>" target="_blank">
                            <div class="card-icon">📋</div>
                            <h3><?php echo htmlspecialchars($course['course_name'] ?? 'Unknown Course'); ?> Attendance</h3>
                            <p>Manage student attendance records, participation, and generate reports</p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a class="admin-card" href="attendance/index.html" target="_blank">
                        <div class="card-icon">📋</div>
                        <h3>Student Attendance</h3>
                        <p>Manage student attendance records, participation, and generate reports</p>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section-container">
            <div class="section-header">
                <h2>Student Justifications</h2>
                <p>View justifications submitted by students in your courses</p>
            </div>
            
            <div class="admin-cards">
                <a class="admin-card" href="justifications.php">
                    <div class="card-icon">📝</div>
                    <h3>View Justifications</h3>
                    <p>See all justifications submitted by students in your courses with their approval status</p>
                </a>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
</body>
</html>