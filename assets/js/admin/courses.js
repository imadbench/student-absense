/**
 * Admin Courses Management Page JavaScript
 */

$(document).ready(function() {
    loadCourses();
    loadProfessors();
    loadGroups();
    
    // Modal handlers
    $('.modal-close, .modal-close-btn').on('click', function() {
        $('.modal').removeClass('active');
    });
    
    $('#btnAddCourse').on('click', function() {
        $('#addCourseModal').addClass('active');
    });
    
    $('#addCourseForm').on('submit', function(e) {
        e.preventDefault();
        addCourse();
    });
    
    $('#searchCourses').on('input', function() {
        filterTable();
    });
    
    function loadCourses() {
        $.ajax({
            url: '../backend/api/courses.php',
            method: 'GET',
            data: { action: 'get_all' },
            success: function(response) {
                if (response.success) {
                    renderCourses(response.data);
                } else {
                    $('#coursesTableBody').html('<tr><td colspan="8" class="loading">' + (response.message || 'Failed to load courses') + '</td></tr>');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error loading courses';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {}
                $('#coursesTableBody').html('<tr><td colspan="8" class="loading">' + errorMsg + '</td></tr>');
            }
        });
    }
    
    function loadProfessors() {
        $.ajax({
            url: '../backend/api/professors.php',
            method: 'GET',
            data: { action: 'get_all' },
            success: function(response) {
                if (response.success) {
                    const $select = $('#professorId');
                    $select.empty().append('<option value="">Select Professor</option>');
                    
                    response.data.forEach(function(professor) {
                        const fullName = professor.first_name + ' ' + professor.last_name;
                        $select.append('<option value="' + professor.user_id + '">' + escapeHtml(fullName) + '</option>');
                    });
                } else {
                    console.error('Error loading professors:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading professors:', error);
                $('#professorId').html('<option value="">Error loading professors</option>');
            }
        });
    }
    
    function loadGroups() {
        $.ajax({
            url: '../backend/api/groups.php',
            method: 'GET',
            data: { action: 'get_all' },
            success: function(response) {
                if (response.success) {
                    const $select = $('#groupId');
                    $select.empty().append('<option value="">Select Group</option>');
                    
                    response.data.forEach(function(group) {
                        const groupLabel = group.group_name + ' (' + group.group_code + ')';
                        $select.append('<option value="' + group.group_id + '">' + escapeHtml(groupLabel) + '</option>');
                    });
                } else {
                    console.error('Error loading groups:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading groups:', error);
                $('#groupId').html('<option value="">Error loading groups</option>');
            }
        });
    }
    
    function renderCourses(courses) {
        const $tbody = $('#coursesTableBody');
        $tbody.empty();
        
        if (courses.length === 0) {
            $tbody.html('<tr><td colspan="8" class="loading">No courses found</td></tr>');
            return;
        }
        
        courses.forEach(function(course) {
            // Format day and time display
            let dayTime = '-';
            if (course.course_day || course.course_time) {
                dayTime = (course.course_day || '') + (course.course_day && course.course_time ? ' ' : '') + (course.course_time || '');
            }
            
            const $row = $('<tr>').html(
                '<td>' + escapeHtml(course.course_code) + '</td>' +
                '<td>' + escapeHtml(course.course_name) + '</td>' +
                '<td>' + escapeHtml((course.professor_first || '') + ' ' + (course.professor_last || '')) + '</td>' +
                '<td>' + escapeHtml(course.group_name || '') + ' (' + escapeHtml(course.group_code || '') + ')</td>' +
                '<td>' + escapeHtml(course.academic_year) + '</td>' +
                '<td>' + escapeHtml(course.semester) + '</td>' +
                '<td>' + escapeHtml(dayTime) + '</td>' +
                '<td><button class="btn btn-sm btn-primary" onclick="viewCourse(' + course.course_id + ')">View</button></td>'
            );
            $tbody.append($row);
        });
    }
    
    function addCourse() {
        const courseCode = $('#courseCode').val().trim();
        const courseName = $('#courseName').val().trim();
        const professorId = $('#professorId').val();
        const groupId = $('#groupId').val();
        const academicYear = $('#academicYear').val().trim();
        const semester = $('#semester').val();
        const courseDay = $('#courseDay').val();
        const courseTime = $('#courseTime').val();
        
        // Validation
        if (!courseCode || !courseName || !professorId || !groupId || !academicYear || !semester) {
            showError('جميع الحقول مطلوبة\nAll fields are required');
            return;
        }
        
        // Disable submit button
        const $submitBtn = $('#addCourseForm button[type="submit"]');
        $submitBtn.prop('disabled', true).text('جاري الإضافة...');
        
        // Send request to API
        $.ajax({
            url: '../backend/api/courses.php',
            method: 'POST',
            data: {
                action: 'add',
                course_code: courseCode,
                course_name: courseName,
                professor_id: professorId,
                group_id: groupId,
                academic_year: academicYear,
                semester: semester,
                course_day: courseDay,
                course_time: courseTime
            },
            success: function(response) {
                $submitBtn.prop('disabled', false).text('Add Course');
                
                if (response.success) {
                    // Clear any previous errors
                    $('#addCourseError').removeClass('active').text('').hide();
                    alert('تمت إضافة المادة بنجاح\nCourse added successfully');
                    $('#addCourseModal').removeClass('active');
                    $('#addCourseForm')[0].reset();
                    loadCourses(); // Refresh the course list
                } else {
                    showError(response.message || 'فشل في إضافة المادة (Failed to add course)');
                }
            },
            error: function(xhr, status, error) {
                $submitBtn.prop('disabled', false).text('Add Course');
                
                let errorMsg = 'خطأ في إضافة المادة\nError adding course';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    if (xhr.status === 0) {
                        errorMsg = 'خطأ في الاتصال. تحقق من اتصالك بالإنترنت\nNetwork error. Please check your connection.';
                    } else {
                        errorMsg = 'خطأ في الخادم\nServer error: ' + xhr.status;
                    }
                }
                showError(errorMsg);
            }
        });
    }
    
    function filterTable() {
        const searchTerm = $('#searchCourses').val().toLowerCase();
        
        $('#coursesTableBody tr').each(function() {
            const $row = $(this);
            const text = $row.text().toLowerCase();
            $row.toggle(!searchTerm || text.includes(searchTerm));
        });
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    function showError(message) {
        const $error = $('#addCourseError');
        $error.html(message.replace(/\n/g, '<br>')).addClass('active').show();
        setTimeout(() => {
            $error.removeClass('active');
        }, 5000);
    }
});

function viewCourse(courseId) {
    // Navigate to course details or attendance management
    window.location.href = '../professor/summary.php?course_id=' + courseId;
}