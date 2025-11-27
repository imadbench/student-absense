<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('administrator');

$request_id = intval($_GET['id'] ?? 0);
if ($request_id <= 0) {
    header('Location: justifications.php');
    exit;
}

// Get justification details
try {
    require_once __DIR__ . '/../backend/includes/db_connect.php';
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT j.*, u.first_name, u.last_name, u.student_id, u.email,
                           COALESCE(s.session_number, MOD(j.session_id, 100)) as session_number,
                           COALESCE(s.session_date, DATE_ADD(NOW(), INTERVAL MOD(j.session_id, 100) WEEK)) as session_date,
                           COALESCE(c.course_name, 'Unknown Course') as course_name
                           FROM justification_requests j
                           JOIN users u ON j.student_id = u.user_id
                           LEFT JOIN attendance_sessions s ON j.session_id = s.session_id
                           LEFT JOIN courses c ON (s.course_id = c.course_id OR FLOOR(j.session_id / 100) = c.course_id)
                           WHERE j.request_id = ?");
    $stmt->execute([$request_id]);
    $justification = $stmt->fetch();
    
    if (!$justification) {
        header('Location: justifications.php');
        exit;
    }
    
    // Check if already reviewed
    if ($justification['status'] !== 'pending') {
        header('Location: justification_detail.php?id=' . $request_id);
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
    <title>Review Justification - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Review Justification</h1>
            <p>Change status of student absence justification</p>
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
                    <a href="../uploads/<?php echo htmlspecialchars($justification['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">View File</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="detail-card">
            <h2>Change Status</h2>
            <form id="statusChangeForm">
                <input type="hidden" id="requestId" value="<?php echo $request_id; ?>">
                <div class="form-group">
                    <label for="newStatus">New Status *</label>
                    <select id="newStatus" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reviewNotes">Review Notes</label>
                    <textarea id="reviewNotes" class="form-control" rows="3" placeholder="Add any notes about your review..."></textarea>
                </div>
                <div id="statusChangeError" class="error-message"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <a href="justification_detail.php?id=<?php echo $request_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    
    <script>
        $(document).ready(function() {
            $('#statusChangeForm').on('submit', function(e) {
                e.preventDefault();
                updateStatus();
            });
            
            function updateStatus() {
                const requestId = $('#requestId').val();
                const status = $('#newStatus').val();
                const reviewNotes = $('#reviewNotes').val().trim();
                
                if (!requestId || !status) {
                    showError('Please select a status');
                    return;
                }
                
                // Disable submit button during submission
                const $submitBtn = $('button[type="submit"]');
                const originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('Updating...');
                
                $.ajax({
                    url: '../backend/api/justification.php',
                    method: 'POST',
                    data: {
                        action: 'review',
                        request_id: requestId,
                        status: status,
                        review_notes: reviewNotes
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Status updated successfully');
                            window.location.href = 'justification_detail.php?id=' + requestId;
                        } else {
                            showError(response.message || 'Failed to update status');
                            $submitBtn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error updating status';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg = response.message || errorMsg;
                        } catch (e) {}
                        showError(errorMsg);
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            }
            
            function showError(message) {
                const $error = $('#statusChangeError');
                $error.text(message).addClass('active');
                setTimeout(() => {
                    $error.removeClass('active');
                }, 5000);
            }
        });
    </script>
</body>
</html>