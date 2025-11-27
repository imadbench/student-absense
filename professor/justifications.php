<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('professor');

// Get justification requests for professor's courses
try {
    require_once __DIR__ . '/../backend/includes/db_connect.php';
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT j.*, u.first_name, u.last_name, u.student_id,
                           COALESCE(s.session_number, MOD(j.session_id, 100)) as session_number,
                           COALESCE(s.session_date, DATE_ADD(NOW(), INTERVAL MOD(j.session_id, 100) WEEK)) as session_date,
                           COALESCE(c.course_name, 'Unknown Course') as course_name,
                           r.first_name as reviewer_first_name, r.last_name as reviewer_last_name
                           FROM justification_requests j
                           JOIN users u ON j.student_id = u.user_id
                           LEFT JOIN attendance_sessions s ON j.session_id = s.session_id
                           LEFT JOIN courses c ON (s.course_id = c.course_id OR FLOOR(j.session_id / 100) = c.course_id)
                           LEFT JOIN users r ON j.reviewed_by = r.user_id
                           WHERE c.professor_id = ?
                           ORDER BY j.submitted_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $justifications = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching professor justifications: " . $e->getMessage());
    $error = "Error loading justifications";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Justifications - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Student Justifications</h1>
            <p>View justifications submitted by students in your courses</p>
        </div>
        
        <div class="actions-bar">
            <a href="home.php" class="btn btn-secondary">← Back to Courses</a>
        </div>
        
        <div class="table-filters">
            <select id="filterStatus" class="filter-select">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message active">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <table class="data-table" id="justificationsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Session</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>File</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                        <th>Review Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="justificationsTableBody">
                    <?php if (empty($justifications)): ?>
                        <tr><td colspan="10" class="loading">No justifications found for your courses</td></tr>
                    <?php else: ?>
                        <?php foreach ($justifications as $j): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($j['first_name'] . ' ' . $j['last_name']); ?><br>
                                    <small><?php echo htmlspecialchars($j['student_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($j['course_name']); ?></td>
                                <td>Session <?php echo htmlspecialchars($j['session_number']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($j['session_date'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($j['reason'], 0, 50)) . (strlen($j['reason']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <?php if ($j['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars('../uploads/' . $j['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $j['status']; ?>">
                                        <?php echo ucfirst($j['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($j['reviewed_by']): ?>
                                        <?php echo htmlspecialchars(($j['reviewer_first_name'] ?? '') . ' ' . ($j['reviewer_last_name'] ?? '')); ?>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($j['reviewed_at']): ?>
                                        <?php echo date('M j, Y', strtotime($j['reviewed_at'])); ?>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="justification_detail.php?id=<?php echo $j['request_id']; ?>" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            document.getElementById('filterStatus').addEventListener('change', function() {
                const statusFilter = this.value.toLowerCase();
                const rows = document.querySelectorAll('#justificationsTableBody tr');
                
                rows.forEach(function(row) {
                    if (!statusFilter) {
                        row.style.display = '';
                        return;
                    }
                    
                    const statusCell = row.querySelector('.status-badge');
                    if (statusCell) {
                        const status = statusCell.textContent.trim().toLowerCase();
                        row.style.display = status === statusFilter ? '' : 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>