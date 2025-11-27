<?php
require_once __DIR__ . '/../backend/includes/auth.php';
require_once __DIR__ . '/../backend/includes/db_connect.php';
requireRole('student');

// Load student attendance records
$attendanceRecords = [];
$summary = [
    'total_sessions' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'excused' => 0
];

try {
    $pdo = getConnection();
    
    // Get attendance records for the student across all enrolled courses
    $stmt = $pdo->prepare("SELECT 
                              ar.status,
                              ar.marked_at,
                              c.course_name,
                              c.course_code,
                              g.group_name,
                              s.session_number,
                              s.session_date,
                              u.first_name as professor_first_name,
                              u.last_name as professor_last_name
                           FROM attendance_records ar
                           JOIN attendance_sessions s ON ar.session_id = s.session_id
                           JOIN courses c ON s.course_id = c.course_id
                           LEFT JOIN `groups` g ON c.group_id = g.group_id
                           LEFT JOIN users u ON c.professor_id = u.user_id
                           WHERE ar.student_id = ?
                           ORDER BY s.session_date DESC, c.course_name");
    $stmt->execute([$_SESSION['user_id']]);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    foreach ($attendanceRecords as $record) {
        $summary['total_sessions']++;
        switch ($record['status']) {
            case 'present':
                $summary['present']++;
                break;
            case 'absent':
                $summary['absent']++;
                break;
            case 'late':
                $summary['late']++;
                break;
            case 'excused':
                $summary['excused']++;
                break;
        }
    }
    
    // Calculate attendance rate
    $summary['attendance_rate'] = $summary['total_sessions'] > 0 ? 
        round((($summary['present'] + $summary['excused']) / $summary['total_sessions']) * 100, 1) : 0;
    
} catch (Exception $e) {
    error_log('Error loading student attendance: ' . $e->getMessage());
    $error = 'Failed to load attendance records. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>My Attendance Records</h1>
            <p>View your attendance history across all courses</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message active">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Attendance Summary Cards -->
        <div class="dashboard-cards">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value"><?php echo $summary['total_sessions']; ?></div>
            </div>
            <div class="stat-card present-card">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Present</div>
                <div class="stat-value"><?php echo $summary['present']; ?></div>
            </div>
            <div class="stat-card absent-card">
                <div class="stat-icon">❌</div>
                <div class="stat-label">Absent</div>
                <div class="stat-value"><?php echo $summary['absent']; ?></div>
            </div>
            <div class="stat-card rate-card">
                <div class="stat-icon">📊</div>
                <div class="stat-label">Attendance Rate</div>
                <div class="stat-value"><?php echo $summary['attendance_rate']; ?>%</div>
            </div>
        </div>
        
        <!-- Attendance Records Table -->
        <div class="section-container">
            <div class="section-header">
                <h2>Attendance History</h2>
                <p>Detailed records of your attendance across all courses</p>
            </div>
            
            <?php if (!empty($attendanceRecords)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Session</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Professor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['course_name']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($record['course_code']); ?></small>
                                    </td>
                                    <td>Session <?php echo htmlspecialchars($record['session_number']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($record['session_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($record['status']); ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($record['professor_first_name'] . ' ' . $record['professor_last_name']); ?>
                                        <br><small><?php echo htmlspecialchars($record['group_name']); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No Attendance Records Found</h3>
                    <p>You don't have any attendance records yet. Attendance will appear here once sessions are marked by your professors.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="section-container">
            <div class="section-header">
                <h2>Attendance Actions</h2>
                <p>Manage your attendance-related activities</p>
            </div>
            
            <div class="admin-cards">
                <a class="admin-card" href="justification.php">
                    <div class="card-icon">📝</div>
                    <h3>Submit Justification</h3>
                    <p>Submit a justification for an absence</p>
                </a>
                
                <a class="admin-card" href="home.php">
                    <div class="card-icon">📚</div>
                    <h3>View Courses</h3>
                    <p>See all available courses</p>
                </a>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
</body>
</html>