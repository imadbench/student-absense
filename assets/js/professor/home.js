/**
 * Professor Home Page JavaScript
 */

$(document).ready(function() {
    loadCourses();
    
    function loadCourses() {
        $.ajax({
            url: '../backend/api/courses.php',
            method: 'GET',
            data: { action: 'get_professor_courses' },
            success: function(response) {
                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        renderCourses(response.data);
                    } else {
                        $('#coursesGrid').html('<div class="loading">No courses found. Please contact administrator.</div>');
                    }
                } else {
                    let message = 'No courses found. Please contact administrator.';
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
            console.log('Rendering course:', course.course_id, course.course_name);
            // Use a stable relative path from the project root to the professor session page
            const courseUrl = `../professor/session/index.php?course_id=${course.course_id}`;
            console.log('Course URL:', courseUrl);
            
            const $card = $('<a>')
                .addClass('course-card')
                .attr('href', courseUrl)
                .attr('data-course-id', course.course_id)
                .on('click', function(e) {
                    e.preventDefault();
                    console.log('Course card clicked!');
                    console.log('Navigating to:', courseUrl);
                    console.log('Course ID:', course.course_id);
                    // Force navigation to sessions page
                    window.location.href = courseUrl;
                })
                .html(`
                    <h3>${escapeHtml(course.course_name || 'Unknown Course')}</h3>
                    <p><strong>Code:</strong> ${escapeHtml(course.course_code || 'N/A')}</p>
                    <p><strong>Group:</strong> ${escapeHtml(course.group_name || 'N/A')} (${escapeHtml(course.group_code || 'N/A')})</p>
                    <p><strong>Academic Year:</strong> ${escapeHtml(course.academic_year || 'N/A')}</p>
                    <p><strong>Semester:</strong> ${escapeHtml(course.semester || 'N/A')}</p>
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
});