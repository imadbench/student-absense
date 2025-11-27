/**
 * Admin Professors Management Page JavaScript
 */

$(document).ready(function() {
    loadProfessors();
    
    // Modal handlers
    $('.modal-close, .modal-close-btn').on('click', function() {
        $('#addProfessorModal').removeClass('active');
    });
    
    $('#btnAddProfessor').on('click', function() {
        $('#addProfessorModal').addClass('active');
        $('#addProfessorForm')[0].reset();
        $('#addProfessorError').removeClass('active').text('').hide();
    });
    
    $('#addProfessorForm').on('submit', function(e) {
        e.preventDefault();
        addProfessor();
    });
    
    $('#searchProfessors').on('input', function() {
        filterTable();
    });
    
    function loadProfessors() {
        $.ajax({
            url: '../backend/api/professors.php',
            method: 'GET',
            data: { action: 'get_all' },
            success: function(response) {
                if (response.success) {
                    renderProfessors(response.data);
                } else {
                    $('#professorsTableBody').html('<tr><td colspan="6" class="loading">' + (response.message || 'Failed to load professors') + '</td></tr>');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error loading professors';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    console.error('Error:', error);
                }
                $('#professorsTableBody').html('<tr><td colspan="6" class="loading">' + errorMsg + '</td></tr>');
            }
        });
    }
    
    function renderProfessors(professors) {
        const $tbody = $('#professorsTableBody');
        $tbody.empty();
        
        if (professors.length === 0) {
            $tbody.html('<tr><td colspan="6" class="loading">No professors found</td></tr>');
            return;
        }
        
        professors.forEach(function(professor) {
            const $row = $('<tr>').html(`
                <td>${escapeHtml(professor.first_name)}</td>
                <td>${escapeHtml(professor.last_name)}</td>
                <td>${escapeHtml(professor.email)}</td>
                <td>${escapeHtml(professor.username)}</td>
                <td>${formatDate(professor.created_at)}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteProfessor(${professor.user_id})">Delete</button>
                </td>
            `);
            $tbody.append($row);
        });
    }
    
    function addProfessor() {
        // Clear previous errors
        $('#addProfessorError').removeClass('active').text('').hide();
        
        const firstName = $('#profFirstName').val().trim();
        const lastName = $('#profLastName').val().trim();
        const email = $('#profEmail').val().trim();
        const username = $('#profUsername').val().trim();
        const password = $('#profPassword').val();
        
        // Validation
        if (!firstName || !lastName || !email || !username || !password) {
            showError('جميع الحقول مطلوبة\nAll fields are required');
            return;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError('البريد الإلكتروني غير صحيح\nInvalid email address');
            return;
        }
        
        // Password validation
        if (password.length < 6) {
            showError('كلمة المرور يجب أن تكون 6 أحرف على الأقل\nPassword must be at least 6 characters');
            return;
        }
        
        // Disable submit button
        const $submitBtn = $('#addProfessorForm button[type="submit"]');
        $submitBtn.prop('disabled', true).text('جاري الإضافة...');
        
        $.ajax({
            url: '../backend/api/professors.php',
            method: 'POST',
            data: {
                action: 'add',
                first_name: firstName,
                last_name: lastName,
                email: email,
                username: username,
                password: password
            },
            success: function(response) {
                $submitBtn.prop('disabled', false).text('Add Professor');
                
                if (response.success) {
                    $('#addProfessorError').removeClass('active').text('').hide();
                    alert('تم إضافة الأستاذ بنجاح\nProfessor added successfully');
                    $('#addProfessorModal').removeClass('active');
                    $('#addProfessorForm')[0].reset();
                    loadProfessors();
                } else {
                    showError(response.message || 'فشل في إضافة الأستاذ (Failed to add professor)');
                }
            },
            error: function(xhr, status, error) {
                $submitBtn.prop('disabled', false).text('Add Professor');
                
                let errorMsg = 'خطأ في إضافة الأستاذ\nError adding professor';
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
        const searchTerm = $('#searchProfessors').val().toLowerCase();
        
        $('#professorsTableBody tr').each(function() {
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
    
    function showError(message) {
        const $error = $('#addProfessorError');
        $error.html(message.replace(/\n/g, '<br>')).addClass('active').show();
        $error[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => {
            $error.removeClass('active');
        }, 10000);
    }
});

// Global function for delete
function deleteProfessor(userId) {
    if (!confirm('Are you sure you want to delete this professor?')) {
        return;
    }
    
    $.ajax({
        url: '../backend/api/professors.php',
        method: 'POST',
        data: {
            action: 'delete',
            user_id: userId
        },
        success: function(response) {
            if (response.success) {
                alert('Professor deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to delete professor'));
            }
        },
        error: function() {
            alert('Error deleting professor');
        }
    });
}

