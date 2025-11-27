<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Student Management</h1>
            <div class="page-actions">
                <button id="btnAddStudent" class="btn btn-primary">➕ Add Student</button>
                <button id="btnImport" class="btn btn-secondary">📥 Import Excel</button>
                <button id="btnExport" class="btn btn-secondary">📤 Export Excel</button>
            </div>
        </div>
        
        <div class="table-filters">
            <input type="text" id="searchStudents" placeholder="Search students..." class="search-input">
        </div>
        
        <div class="table-container">
            <table class="data-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr><td colspan="6" class="loading">Loading students...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Add Student Modal -->
        <div id="addStudentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Student</h2>
                    <span class="modal-close">&times;</span>
                </div>
                <form id="addStudentForm" class="modal-body">
                    <div class="form-group">
                        <label for="studentId">Student ID *</label>
                        <input type="text" id="studentId" required>
                    </div>
                    <div class="form-group">
                        <label for="firstName">First Name *</label>
                        <input type="text" id="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name *</label>
                        <input type="text" id="lastName" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="groupId">Group *</label>
                        <select id="groupId" required>
                            <option value="">Select Group</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username">
                        <small>Leave empty to use Student ID as username</small>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password">
                        <small>Leave empty to use Student ID as default password</small>
                    </div>
                    <div id="addStudentError" class="error-message"></div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Add Student</button>
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Import Modal -->
        <div id="importModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Import Students from Excel</h2>
                    <span class="modal-close">&times;</span>
                </div>
                <form id="importForm" class="modal-body" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="importFile">Excel File (CSV format) *</label>
                        <input type="file" id="importFile" accept=".csv,.xlsx,.xls" required>
                        <small>Format: Student ID, First Name, Last Name, Email</small>
                    </div>
                    <div id="importError" class="error-message"></div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Import</button>
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script src="../assets/js/admin/students.js"></script>
</body>
</html>