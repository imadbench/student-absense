<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('student');

$course_id = intval($_GET['course_id'] ?? 0);
if ($course_id <= 0) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>My Attendance</h1>
            <div class="course-info" id="courseInfo">
                <div class="loading">Loading course information...</div>
            </div>
        </div>
        
        <div class="dashboard-cards">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value" id="totalSessions">0</div>
            </div>
            <div class="stat-card present-card">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Present</div>
                <div class="stat-value" id="totalPresent">0</div>
            </div>
            <div class="stat-card absent-card">
                <div class="stat-icon">❌</div>
                <div class="stat-label">Absent</div>
                <div class="stat-value" id="totalAbsent">0</div>
            </div>
            <div class="stat-card rate-card">
                <div class="stat-icon">📊</div>
                <div class="stat-label">Attendance Rate</div>
                <div class="stat-value" id="attendanceRate">0%</div>
            </div>
        </div>
        
        <div class="section-container">
            <div class="section-header">
                <h2>Attendance Records</h2>
            </div>
            <div class="attendance-table-container">
                <table class="attendance-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Session #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Justification</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <tr><td colspan="5" class="loading">Loading attendance records...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Justification Modal -->
        <div id="justificationModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Submit Justification</h2>
                    <span class="modal-close">&times;</span>
                </div>
                <form id="justificationForm" class="modal-body">
                    <input type="hidden" id="justificationSessionId">
                    <div class="form-group">
                        <label for="justificationReason">Reason *</label>
                        <textarea id="justificationReason" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="justificationFile">Supporting Document (Optional)</label>
                        <input type="file" id="justificationFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small>Max size: 5MB. Allowed: PDF, JPG, PNG, DOC, DOCX</small>
                    </div>
                    <div id="justificationError" class="error-message"></div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script>
        const COURSE_ID = <?php echo $course_id; ?>;
        // Pass the student ID to JavaScript
        const STUDENT_ID = <?php echo $_SESSION['user_id']; ?>;
        // Make it available globally for the JS file
        window.STUDENT_ID = STUDENT_ID;
    </script>
    <script src="../assets/js/student/attendance.js"></script>
</body>
</html>