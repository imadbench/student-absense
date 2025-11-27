/**
 * Student Home Page JavaScript
 */

$(document).ready(function() {
    loadCourses();
    
    function loadCourses() {
        $.ajax({
            url: '../backend/api/courses.php',
            method: 'GET',
            data: { action: 'get_student_courses' },
            success: function(response) {
                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        renderCourses(response.data);
                    } else {
                        $('#coursesGrid').html('<div class="loading">No enrolled courses found.</div>');
                    }
                } else {
                    let message = 'No enrolled courses found.';
                    if (response.message) {
                        message = response.message;
                    }
                    $('#coursesGrid').html('<div class="loading">' + message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Course loading error:', xhr, status, error);
                let errorMessage = 'Error loading courses. Please try again.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        errorMessage = 'Error: ' + xhr.responseJSON.error;
                    } else if (xhr.responseJSON.message) {
                        errorMessage = 'Error: ' + xhr.responseJSON.message;
                    }
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMessage = 'Error: ' + response.error;
                        } else if (response.message) {
                            errorMessage = 'Error: ' + response.message;
                        }
                    } catch (e) {
                        // Not JSON response
                        errorMessage = 'Error: ' + xhr.responseText;
                    }
                }
                $('#coursesGrid').html('<div class="loading">' + errorMessage + '</div>');
            }
        });
    }
    
    function renderCourses(courses) {
        const $grid = $('#coursesGrid');
        $grid.empty();
        
        courses.forEach(function(course) {
            const professorName = (course.professor_first || '') + ' ' + (course.professor_last || '');
            const $card = $('<a>')
                .addClass('course-card')
                .attr('href', `attendance.php?course_id=${course.course_id}`)
                .html(`
                    <h3>${escapeHtml(course.course_name || 'Unknown Course')}</h3>
                    <p><strong>Code:</strong> ${escapeHtml(course.course_code || 'N/A')}</p>
                    <p><strong>Group:</strong> ${escapeHtml(course.group_name || 'N/A')}</p>
                    <p><strong>Professor:</strong> ${escapeHtml(professorName.trim() || 'N/A')}</p>
                    <p><strong>Academic Year:</strong> ${escapeHtml(course.academic_year || 'N/A')}</p>
                    ${course.course_day || course.course_time ? 
                        `<p><strong>Schedule:</strong> ${escapeHtml(course.course_day || '')}${course.course_time ? ' at ' + formatTime(course.course_time) : ''}</p>` : ''}
                `);
            
            $grid.append($card);
        });
    }
    
    function escapeHtml(text) {
        if (text === null || text === undefined) return 'N/A';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    function formatTime(timeString) {
        if (!timeString) return '';
        const timeParts = timeString.split(':');
        if (timeParts.length < 2) return timeString;
        
        const hours = parseInt(timeParts[0]);
        const minutes = timeParts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = hours % 12 || 12;
        
        return `${formattedHours}:${minutes} ${ampm}`;
    }
});