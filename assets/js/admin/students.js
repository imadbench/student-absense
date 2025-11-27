/**
 * Admin Students Management Page JavaScript
 */

$(document).ready(function() {
    console.log('Students page loaded');
    
    // Load data
    loadStudents();
    loadGroups();
    
    // Modal handlers
    $('.modal-close, .modal-close-btn').on('click', function() {
        $('.modal').removeClass('active');
    });
    
    // Button handlers
    $('#btnAddStudent').on('click', function() {
        console.log('Add Student button clicked');
        $('#addStudentModal').addClass('active');
    });
    
    $('#btnImport').on('click', function() {
        console.log('Import button clicked');
        $('#importModal').addClass('active');
    });
    
    $('#btnExport').on('click', function() {
        console.log('Export button clicked');
        exportStudents();
    });
    
    // Form handlers
    $('#addStudentForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Add student form submitted');
        addStudent();
    });
    
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Import form submitted');
        importStudents();
    });
    
    // Search handler
    $('#searchStudents').on('input', function() {
        filterTable();
    });
    
    function loadStudents() {
        console.log('Loading students...');
        $.ajax({
            url: '../backend/api/students.php',
            method: 'GET',
            data: { action: 'get_all' },
            success: function(response) {
                console.log('Students loaded:', response);
                if (response.success) {
                    renderStudents(response.data);
                } else {
                    showError(response.message || 'Failed to load students');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading students:', xhr, status, error);
                showError('Error loading students: ' + error);
            }
        });
    }
    
    function loadGroups() {
        console.log('Loading groups...');
        $.ajax({
            url: '../backend/api/groups.php',
            method: 'GET',
            data: { action: 'get_all' },
            success: function(response) {
                console.log('Groups loaded:', response);
                if (response.success) {
                    const $select = $('#groupId');
                    $select.empty().append('<option value="">Select Group</option>');
                    
                    response.data.forEach(function(group) {
                        const groupLabel = group.group_name + ' (' + group.group_code + ')';
                        $select.append('<option value="' + group.group_id + '">' + escapeHtml(groupLabel) + '</option>');
                    });
                } else {
                    console.error('Error loading groups:', response.message);
                    showError('Error loading groups: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading groups:', xhr, status, error);
                $('#groupId').html('<option value="">Error loading groups</option>');
                showError('Error loading groups: ' + error);
            }
        });
    }
    
    function renderStudents(students) {
        const $tbody = $('#studentsTableBody');
        $tbody.empty();
        
        if (students.length === 0) {
            $tbody.html('<tr><td colspan="6" class="loading">No students found</td></tr>');
            return;
        }
        
        students.forEach(function(student) {
            const $row = $('<tr>').html(
                '<td>' + escapeHtml(student.student_id || 'N/A') + '</td>' +
                '<td>' + escapeHtml(student.first_name) + '</td>' +
                '<td>' + escapeHtml(student.last_name) + '</td>' +
                '<td>' + escapeHtml(student.email) + '</td>' +
                '<td>' + escapeHtml(student.username) + '</td>' +
                '<td><button class="btn btn-danger btn-sm" onclick="deleteStudent(' + student.user_id + ')">Delete</button></td>'
            );
            $tbody.append($row);
        });
    }
    
    function addStudent() {
        // Clear previous errors
        $('#addStudentError').removeClass('active').text('').hide();
        
        const studentId = $('#studentId').val().trim();
        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const email = $('#email').val().trim();
        const groupId = $('#groupId').val();
        const username = $('#username').val().trim();
        const password = $('#password').val();
        
        // Validation
        if (!studentId || !firstName || !lastName || !email) {
            showError('جميع الحقول المطلوبة يجب ملؤها\nAll required fields must be filled');
            return;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError('البريد الإلكتروني غير صحيح\nInvalid email address');
            return;
        }
        
        // Set default username if empty
        const finalUsername = username || studentId;
        // Set default password if empty
        const finalPassword = password || studentId;
        
        // Disable submit button
        const $submitBtn = $('#addStudentForm button[type="submit"]');
        $submitBtn.prop('disabled', true).text('جاري الإضافة...');
        
        $.ajax({
            url: '../backend/api/students.php',
            method: 'POST',
            data: {
                action: 'add',
                student_id: studentId,
                first_name: firstName,
                last_name: lastName,
                email: email,
                group_id: groupId,
                username: finalUsername,
                password: finalPassword
            },
            success: function(response) {
                $submitBtn.prop('disabled', false).text('Add Student');
                
                if (response.success) {
                    // Clear any previous errors
                    $('#addStudentError').removeClass('active').text('').hide();
                    alert('تم إضافة الطالب بنجاح\nStudent added successfully');
                    $('#addStudentModal').removeClass('active');
                    $('#addStudentForm')[0].reset();
                    loadStudents();
                } else {
                    showError(response.message || 'فشل في إضافة الطالب (Failed to add student)');
                }
            },
            error: function(xhr, status, error) {
                $submitBtn.prop('disabled', false).text('Add Student');
                
                let errorMsg = 'خطأ في إضافة الطالب\nError adding student';
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
    
    function importStudents() {
        const fileInput = document.getElementById('importFile');
        const file = fileInput.files[0];
        
        if (!file) {
            showError('Please select a file to import', 'import');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'import_excel');
        formData.append('file', file);
        
        const $submitBtn = $('#importForm button[type="submit"]');
        $submitBtn.prop('disabled', true).text('جاري الاستيراد...');
        
        $.ajax({
            url: '../backend/api/students.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $submitBtn.prop('disabled', false).text('Import');
                
                if (response.success) {
                    const imported = response.data.imported;
                    const errors = response.data.errors || [];
                    
                    let message = 'Successfully imported ' + imported + ' students.';
                    if (errors.length > 0) {
                        message += '\n\nErrors:\n' + errors.join('\n');
                    }
                    
                    alert(message);
                    $('#importModal').removeClass('active');
                    $('#importForm')[0].reset();
                    loadStudents();
                } else {
                    showError(response.message || 'Failed to import students', 'import');
                }
            },
            error: function(xhr, status, error) {
                $submitBtn.prop('disabled', false).text('Import');
                showError('Error importing students: ' + error, 'import');
            }
        });
    }
    
    function exportStudents() {
        window.location.href = '../backend/api/students.php?action=export_excel';
    }
    
    function filterTable() {
        const searchTerm = $('#searchStudents').val().toLowerCase();
        
        $('#studentsTableBody tr').each(function() {
            const $row = $(this);
            const text = $row.text().toLowerCase();
            $row.toggle(!searchTerm || text.includes(searchTerm));
        });
    }
    
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text || '').replace(/[&<>"']/g, m => map[m]);
    }
    
    function showError(message, type = 'add') {
        console.error('Error:', message);
        // Show error in add student form
        const $addError = $('#addStudentError');
        if ($addError.length) {
            $addError.html(message.replace(/\n/g, '<br>')).addClass('active').show();
            // Scroll to error
            $addError[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            setTimeout(() => {
                $addError.removeClass('active');
            }, 10000);
        }
        
        // Also show alert for visibility
        alert('Error: ' + message);
    }
});

// Global function for delete
function deleteStudent(userId) {
    if (!confirm('Are you sure you want to delete this student?')) {
        return;
    }
    
    $.ajax({
        url: '../backend/api/students.php',
        method: 'POST',
        data: {
            action: 'delete',
            user_id: userId
        },
        success: function(response) {
            if (response.success) {
                alert('Student deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to delete student'));
            }
        },
        error: function(xhr, status, error) {
            alert('Error deleting student: ' + error);
        }
    });
}