<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('professor');

$request_id = intval($_GET['id'] ?? 0);
if ($request_id <= 0) {
    header('Location: justifications.php');
    exit;
}

// Get justification details - only for professor's courses
try {
    require_once __DIR__ . '/../backend/includes/db_connect.php';
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT j.*, u.first_name, u.last_name, u.student_id, u.email,
                           COALESCE(s.session_number, MOD(j.session_id, 100)) as session_number,
                           COALESCE(s.session_date, DATE_ADD(NOW(), INTERVAL MOD(j.session_id, 100) WEEK)) as session_date,
                           COALESCE(c.course_name, 'Unknown Course') as course_name,
                           r.first_name as reviewer_first_name, r.last_name as reviewer_last_name
                           FROM justification_requests j
                           JOIN users u ON j.student_id = u.user_id
                           LEFT JOIN attendance_sessions s ON j.session_id = s.session_id
                           LEFT JOIN courses c ON (s.course_id = c.course_id OR FLOOR(j.session_id / 100) = c.course_id)
                           LEFT JOIN users r ON j.reviewed_by = r.user_id
                           WHERE j.request_id = ? AND c.professor_id = ?");
    $stmt->execute([$request_id, $_SESSION['user_id']]);
    $justification = $stmt->fetch();
    
    if (!$justification) {
        header('Location: justifications.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching justification: " . $e->getMessage());
    $error = "Error loading justification details";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justification Details - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Justification Details</h1>
            <p>View student absence justification</p>
        </div>
        
        <div class="actions-bar">
            <a href="justifications.php" class="btn btn-secondary">← Back to Justifications</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message active">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="detail-card">
            <h2>Student Information</h2>
            <div class="detail-item">
                <strong>Name:</strong> 
                <span><?php echo htmlspecialchars($justification['first_name'] . ' ' . $justification['last_name']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Student ID:</strong> 
                <span><?php echo htmlspecialchars($justification['student_id']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Email:</strong> 
                <span><?php echo htmlspecialchars($justification['email']); ?></span>
            </div>
        </div>
        
        <div class="detail-card">
            <h2>Course Information</h2>
            <div class="detail-item">
                <strong>Course:</strong> 
                <span><?php echo htmlspecialchars($justification['course_name']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Session:</strong> 
                <span>Session <?php echo htmlspecialchars($justification['session_number']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Date:</strong> 
                <span><?php echo htmlspecialchars($justification['session_date']); ?></span>
            </div>
        </div>
        
        <div class="detail-card">
            <h2>Justification Details</h2>
            <div class="detail-item">
                <strong>Submitted:</strong> 
                <span><?php echo date('F j, Y g:i A', strtotime($justification['submitted_at'])); ?></span>
            </div>
            <div class="detail-item">
                <strong>Status:</strong> 
                <span class="status-badge status-<?php echo $justification['status']; ?>">
                    <?php echo ucfirst($justification['status']); ?>
                </span>
            </div>
            <div class="detail-item">
                <strong>Reason:</strong> 
                <p><?php echo nl2br(htmlspecialchars($justification['reason'])); ?></p>
            </div>
            <?php if ($justification['file_path']): ?>
                <div class="detail-item">
                    <strong>Supporting Document:</strong> 
                    <a href="<?php echo htmlspecialchars('../uploads/' . $justification['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">View File</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($justification['status'] !== 'pending'): ?>
            <div class="detail-card">
                <h2>Review Information</h2>
                <div class="detail-item">
                    <strong>Reviewed By:</strong> 
                    <span><?php echo htmlspecialchars(($justification['reviewer_first_name'] ?? '') . ' ' . ($justification['reviewer_last_name'] ?? 'Unknown')); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Review Date:</strong> 
                    <span><?php echo date('F j, Y g:i A', strtotime($justification['reviewed_at'])); ?></span>
                </div>
                <?php if ($justification['review_notes']): ?>
                    <div class="detail-item">
                        <strong>Review Notes:</strong> 
                        <p><?php echo nl2br(htmlspecialchars($justification['review_notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
</body>
</html>