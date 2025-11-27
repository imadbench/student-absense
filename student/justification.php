<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Justification - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Submit Justification</h1>
            <p>Provide a detailed reason for your absence and upload supporting documents if available</p>
        </div>
        
        <div class="justification-form-container">
            <form id="justificationForm" class="justification-form">
                <div class="form-group">
                    <label for="courseSelect">Select Course *</label>
                    <select id="courseSelect" class="form-control" required>
                        <option value="">Loading courses...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sessionSelect">Select Session *</label>
                    <select id="sessionSelect" class="form-control" required disabled>
                        <option value="">Select a course first</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="justificationReason">Reason for Absence *</label>
                    <textarea id="justificationReason" class="form-control" placeholder="Please provide a detailed explanation for your absence. Include any relevant circumstances..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="justificationFile">Supporting Document (Optional)</label>
                    <input type="file" id="justificationFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <small>Max size: 5MB. Allowed formats: PDF, JPG, PNG, DOC, DOCX</small>
                </div>
                
                <div id="justificationError" class="error-message"></div>
                <div id="justificationSuccess" class="success-message"></div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Justification</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='home.php'">Back to Dashboard</button>
                </div>
            </form>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script>
        $(document).ready(function() {
            // Load student courses
            loadCourses();
            
            // Handle course selection change
            $('#courseSelect').on('change', function() {
                const courseId = $(this).val();
                if (courseId) {
                    loadSessions(courseId);
                } else {
                    $('#sessionSelect').prop('disabled', true).html('<option value="">Select a course first</option>');
                }
            });
            
            // Handle form submission
            $('#justificationForm').on('submit', function(e) {
                e.preventDefault();
                submitJustification();
            });
        });
        
        function loadCourses() {
            $.ajax({
                url: '../backend/api/courses.php',
                method: 'GET',
                data: { action: 'get_student_courses' },
                success: function(response) {
                    if (response.success) {
                        const courses = response.data;
                        const $select = $('#courseSelect');
                        $select.empty();
                        
                        if (courses.length > 0) {
                            $select.append('<option value="">-- Select a Course --</option>');
                            courses.forEach(function(course) {
                                $select.append(`<option value="${course.course_id}">${course.course_name} (${course.course_code}) - ${course.group_name}</option>`);
                            });
                        } else {
                            $select.append('<option value="">No courses found</option>');
                        }
                    } else {
                        $('#courseSelect').html('<option value="">Error loading courses</option>');
                    }
                },
                error: function() {
                    $('#courseSelect').html('<option value="">Error loading courses</option>');
                }
            });
        }
        
        function loadSessions(courseId) {
            $.ajax({
                url: '../backend/api/attendance.php',
                method: 'GET',
                data: { action: 'get_sessions', course_id: courseId },
                success: function(response) {
                    if (response.success) {
                        const sessions = response.data;
                        const $select = $('#sessionSelect');
                        $select.prop('disabled', false).empty();
                        
                        if (sessions.length > 0) {
                            $select.append('<option value="">-- Select a Session --</option>');
                            sessions.forEach(function(session) {
                                const sessionDate = new Date(session.session_date).toLocaleDateString();
                                $select.append(`<option value="${session.session_id}">Session ${session.session_number} - ${sessionDate}</option>`);
                            });
                        } else {
                            $select.append('<option value="">No sessions found for this course</option>');
                        }
                    } else {
                        $('#sessionSelect').prop('disabled', true).html('<option value="">Error loading sessions</option>');
                    }
                },
                error: function() {
                    $('#sessionSelect').prop('disabled', true).html('<option value="">Error loading sessions</option>');
                }
            });
        }
        
        function submitJustification() {
            const courseId = $('#courseSelect').val();
            const sessionId = $('#sessionSelect').val();
            const reason = $('#justificationReason').val().trim();
            const file = $('#justificationFile')[0].files[0];
            
            // Validation
            if (!courseId) {
                showError('Please select a course');
                return;
            }
            
            if (!sessionId) {
                showError('Please select a session');
                return;
            }
            
            if (!reason) {
                showError('Please provide a reason for your absence');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'submit');
            formData.append('session_id', sessionId);
            formData.append('reason', reason);
            if (file) {
                formData.append('file', file);
            }
            
            // Disable submit button during submission
            const $submitBtn = $('button[type="submit"]');
            const originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: '../backend/api/justification.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showSuccess('Justification submitted successfully!');
                        $('#justificationForm')[0].reset();
                        $('#sessionSelect').prop('disabled', true).html('<option value="">Select a course first</option>');
                    } else {
                        showError(response.message || 'Failed to submit justification');
                    }
                },
                error: function() {
                    showError('Error submitting justification. Please try again.');
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }
        
        function showError(message) {
            $('#justificationError').text(message).addClass('active');
            $('#justificationSuccess').removeClass('active');
        }
        
        function showSuccess(message) {
            $('#justificationSuccess').text(message).addClass('active');
            $('#justificationError').removeClass('active');
        }
    </script>
</body>
</html>