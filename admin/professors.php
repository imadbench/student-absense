<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Management - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Professor Management</h1>
            <div class="page-actions">
                <button id="btnAddProfessor" class="btn btn-primary">➕ Add Professor</button>
            </div>
        </div>
        
        <div class="table-filters">
            <input type="text" id="searchProfessors" placeholder="Search professors..." class="search-input">
        </div>
        
        <div class="table-container">
            <table class="data-table" id="professorsTable">
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="professorsTableBody">
                    <tr><td colspan="6" class="loading">Loading professors...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Add Professor Modal -->
        <div id="addProfessorModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Professor</h2>
                    <span class="modal-close">&times;</span>
                </div>
                <form id="addProfessorForm" class="modal-body">
                    <div class="form-group">
                        <label for="profFirstName">First Name *</label>
                        <input type="text" id="profFirstName" required>
                    </div>
                    <div class="form-group">
                        <label for="profLastName">Last Name *</label>
                        <input type="text" id="profLastName" required>
                    </div>
                    <div class="form-group">
                        <label for="profEmail">Email *</label>
                        <input type="email" id="profEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="profUsername">Username *</label>
                        <input type="text" id="profUsername" required>
                        <small>Must be unique</small>
                    </div>
                    <div class="form-group">
                        <label for="profPassword">Password *</label>
                        <input type="password" id="profPassword" required>
                        <small>Minimum 6 characters</small>
                    </div>
                    <div id="addProfessorError" class="error-message"></div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Add Professor</button>
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script src="../assets/js/admin/professors.js"></script>
</body>
</html>

