<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justifications - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Justification Requests</h1>
            <p>Review and manage student absence justifications</p>
        </div>
        
        <div class="actions-bar">
            <a href="all_justifications.php" class="btn btn-secondary">View All Justifications</a>
        </div>
        
        <div class="table-filters">
            <select id="filterStatus" class="filter-select">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="justificationsTableBody">
                    <tr><td colspan="8" class="loading">Loading justifications...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Review Modal -->
        <div id="reviewModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Review Justification</h2>
                    <span class="modal-close">&times;</span>
                </div>
                <form id="reviewForm" class="modal-body">
                    <input type="hidden" id="reviewRequestId">
                    <div class="form-group">
                        <label>Student</label>
                        <p id="reviewStudentName"></p>
                    </div>
                    <div class="form-group">
                        <label>Course</label>
                        <p id="reviewCourseName"></p>
                    </div>
                    <div class="form-group">
                        <label>Session</label>
                        <p id="reviewSessionInfo"></p>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <p id="reviewReason"></p>
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select id="reviewStatus" required>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reviewNotes">Review Notes</label>
                        <textarea id="reviewNotes" rows="3"></textarea>
                    </div>
                    <div id="reviewError" class="error-message"></div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script src="../assets/js/admin/justifications.js"></script>
</body>
</html>