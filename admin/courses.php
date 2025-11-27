<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Course Management</h1>
            <div class="page-actions">
                <button id="btnAddCourse" class="btn btn-primary">➕ Add Course</button>
            </div>
        </div>
        
        <div class="table-filters">
            <input type="text" id="searchCourses" placeholder="Search courses..." class="search-input">
        </div>
        
        <div class="table-container">
            <table class="data-table" id="coursesTable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Professor</th>
                        <th>Group</th>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>Day & Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="coursesTableBody">
                    <tr><td colspan="8" class="loading">Loading courses...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Add Course Modal -->
        <div id="addCourseModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Course</h2>
                    <span class="modal-close">&times;</span>
                </div>
                <form id="addCourseForm" class="modal-body">
                    <div class="form-group">
                        <label for="courseCode">Course Code *</label>
                        <input type="text" id="courseCode" required>
                    </div>
                    <div class="form-group">
                        <label for="courseName">Course Name *</label>
                        <input type="text" id="courseName" required>
                    </div>
                    <div class="form-group">
                        <label for="professorId">Professor *</label>
                        <select id="professorId" required>
                            <option value="">Select Professor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="groupId">Group *</label>
                        <select id="groupId" required>
                            <option value="">Select Group</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="academicYear">Academic Year *</label>
                        <input type="text" id="academicYear" placeholder="2024/2025" required>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester *</label>
                        <select id="semester" required>
                            <option value="">Select Semester</option>
                            <option value="Fall">Fall</option>
                            <option value="Spring">Spring</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="courseDay">Course Day</label>
                        <select id="courseDay">
                            <option value="">Select Day</option>
                            <option value="Sunday">Sunday</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="courseTime">Course Time</label>
                        <input type="time" id="courseTime">
                    </div>
                    <div id="addCourseError" class="error-message"></div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Add Course</button>
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script src="../assets/js/admin/courses.js"></script>
</body>
</html>