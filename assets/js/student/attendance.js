/**
 * Student Attendance Page JavaScript
 */

$(document).ready(function() {
    loadAttendance();
    
    // Modal handlers
    $('.modal-close, .modal-close-btn').on('click', function() {
        $('#justificationModal').removeClass('active');
    });
    
    $('#justificationForm').on('submit', function(e) {
        e.preventDefault();
        submitJustification();
    });
    
    function loadAttendance() {
        // Load course info
        loadCourseInfo();
        
        // Load attendance records
        loadAttendanceRecords();
    }
    
    function loadCourseInfo() {
        // This would need to fetch course info - simplified
        $('#courseInfo').html('<p>Loading course information...</p>');
    }
    
    function loadAttendanceRecords() {
        // Get sessions for this course and attendance records
        $.ajax({
            url: '../backend/api/attendance.php',
            method: 'GET',
            data: { action: 'get_sessions', course_id: COURSE_ID },
            success: function(response) {
                if (response.success) {
                    const sessions = response.data;
                    loadRecordsForSessions(sessions);
                }
            }
        });
    }
    
    function loadRecordsForSessions(sessions) {
        const $tbody = $('#attendanceTableBody');
        $tbody.empty();
        
        let totalSessions = 0;
        let totalPresent = 0;
        let totalAbsent = 0;
        
        if (sessions.length === 0) {
            $tbody.html('<tr><td colspan="5" class="loading">No sessions found for this course.</td></tr>');
            return;
        }
        
        sessions.forEach(function(session, index) {
            totalSessions++;
            
            // Get attendance record for this session
            $.ajax({
                url: '../backend/api/attendance.php',
                method: 'GET',
                data: { action: 'get_attendance', session_id: session.session_id },
                success: function(response) {
                    if (response.success) {
                        const records = response.data;
                        // The student ID should be passed from the PHP page
                        // For now, we'll use a placeholder that will be replaced
                        const studentRecord = records.find(r => r.student_id == window.STUDENT_ID);
                        
                        if (studentRecord) {
                            if (studentRecord.status === 'present') totalPresent++;
                            else if (studentRecord.status === 'absent') totalAbsent++;
                            else if (studentRecord.status === 'late') totalAbsent++;
                            else if (studentRecord.status === 'excused') totalPresent++;
                            
                            renderAttendanceRow(session, studentRecord);
                        } else {
                            totalAbsent++;
                            renderAttendanceRow(session, { status: 'absent' });
                        }
                        
                        // Update stats after all records loaded
                        if (index === sessions.length - 1) {
                            updateStats(totalSessions, totalPresent, totalAbsent);
                        }
                    }
                }
            });
        });
    }
    
    function renderAttendanceRow(session, record) {
        const status = record.status || 'absent';
        const statusClass = `status-${status}`;
        
        // Check if justification exists
        const justificationStatus = record.justification_status || 'none';
        
        const $row = $('<tr>').html(`
            <td>${session.session_number}</td>
            <td>${formatDate(session.session_date)}</td>
            <td><span class="status-badge ${statusClass}">${status.toUpperCase()}</span></td>
            <td>
                ${justificationStatus !== 'none' ? 
                    `<span class="status-badge status-${justificationStatus}">${justificationStatus.toUpperCase()}</span>` : 
                    '-'}
            </td>
            <td>
                ${status === 'absent' ? 
                    `<button class="btn btn-sm btn-primary" onclick="openJustificationModal(${session.session_id})">Submit Justification</button>` : 
                    '-'}
            </td>
        `);
        
        $('#attendanceTableBody').append($row);
    }
    
    function updateStats(total, present, absent) {
        $('#totalSessions').text(total);
        $('#totalPresent').text(present);
        $('#totalAbsent').text(absent);
        const rate = total > 0 ? Math.round((present / total) * 100) : 0;
        $('#attendanceRate').text(rate + '%');
    }
    
    function openJustificationModal(sessionId) {
        $('#justificationSessionId').val(sessionId);
        $('#justificationForm')[0].reset();
        $('#justificationError').removeClass('active').text('');
        $('#justificationModal').addClass('active');
    }
    
    function submitJustification() {
        const sessionId = $('#justificationSessionId').val();
        const reason = $('#justificationReason').val().trim();
        const file = $('#justificationFile')[0].files[0];
        
        if (!reason) {
            showError('Please provide a reason');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'submit');
        formData.append('session_id', sessionId);
        formData.append('reason', reason);
        if (file) {
            formData.append('file', file);
        }
        
        $.ajax({
            url: '../backend/api/justification.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Justification submitted successfully');
                    $('#justificationModal').removeClass('active');
                    loadAttendanceRecords();
                } else {
                    showError(response.message || 'Failed to submit justification');
                }
            },
            error: function() {
                showError('Error submitting justification');
            }
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
    
    function showError(message) {
        const $error = $('#justificationError');
        $error.text(message).addClass('active');
    }
});

// Global function for modal
function openJustificationModal(sessionId) {
    $('#justificationSessionId').val(sessionId);
    $('#justificationForm')[0].reset();
    $('#justificationError').removeClass('active').text('');
    $('#justificationModal').addClass('active');
}

