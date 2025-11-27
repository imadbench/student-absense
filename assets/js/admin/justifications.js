/**
 * Admin Justifications Page JavaScript
 */

$(document).ready(function() {
    loadJustifications();
    
    // Modal handlers
    $('.modal-close, .modal-close-btn').on('click', function() {
        $('.modal').removeClass('active');
    });
    
    $('#filterStatus').on('change', function() {
        filterTable();
    });
    
    $('#reviewForm').on('submit', function(e) {
        e.preventDefault();
        submitReview();
    });
    
    function loadJustifications() {
        $.ajax({
            url: '../backend/api/justification.php',
            method: 'GET',
            data: { action: 'get_pending' },
            success: function(response) {
                if (response.success) {
                    renderJustifications(response.data);
                } else {
                    $('#justificationsTableBody').html('<tr><td colspan="8" class="loading">' + (response.message || 'No justifications found') + '</td></tr>');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error loading justifications';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {}
                $('#justificationsTableBody').html('<tr><td colspan="8" class="loading">' + errorMsg + '</td></tr>');
            }
        });
    }
    
    function renderJustifications(justifications) {
        const $tbody = $('#justificationsTableBody');
        $tbody.empty();
        
        if (justifications.length === 0) {
            $tbody.html('<tr><td colspan="8" class="loading">No justifications found</td></tr>');
            return;
        }
        
        justifications.forEach(function(j) {
            const statusClass = `status-${j.status}`;
            const fileLink = j.file_path ? 
                `<a href="../uploads/${escapeHtml(j.file_path)}" target="_blank" class="btn btn-sm">View File</a>` : 
                '<span>-</span>';
            
            const $row = $('<tr>').html(`
                <td>${escapeHtml((j.first_name || '') + ' ' + (j.last_name || ''))}<br><small>${escapeHtml(j.student_id || '')}</small></td>
                <td>${escapeHtml(j.course_name || '')}</td>
                <td>Session ${escapeHtml(j.session_number || '')}</td>
                <td>${formatDate(j.session_date)}</td>
                <td>${escapeHtml(j.reason || '').substring(0, 50)}${j.reason && j.reason.length > 50 ? '...' : ''}</td>
                <td>${fileLink}</td>
                <td><span class="status-badge ${statusClass}">${escapeHtml((j.status || 'pending').toUpperCase())}</span></td>
                <td>
                    <a href="justification_view.php?id=${j.request_id}" class="btn btn-sm btn-info">View</a>
                    ${j.status === 'pending' ? 
                        `<button class="btn btn-sm btn-primary" onclick="openReviewModal(${j.request_id})">Review</button>` : 
                        '<small>Reviewed</small>'}
                </td>
            `);
            $tbody.append($row);
        });
    }
    
    function openReviewModal(requestId) {
        // Load request details
        const $row = $(`tr:has(button[onclick*="${requestId}"])`);
        if ($row.length) {
            const studentName = $row.find('td:eq(0)').text().trim();
            const courseName = $row.find('td:eq(1)').text().trim();
            const sessionInfo = $row.find('td:eq(2)').text().trim();
            const reason = $row.closest('tr').find('td:eq(4)').text().trim();
            
            $('#reviewRequestId').val(requestId);
            $('#reviewStudentName').text(studentName);
            $('#reviewCourseName').text(courseName);
            $('#reviewSessionInfo').text(sessionInfo);
            $('#reviewReason').text(reason);
            $('#reviewStatus').val('approved');
            $('#reviewNotes').val('');
            $('#reviewError').removeClass('active').text('').hide();
            
            $('#reviewModal').addClass('active');
        }
    }
    
    function submitReview() {
        const requestId = $('#reviewRequestId').val();
        const status = $('#reviewStatus').val();
        const reviewNotes = $('#reviewNotes').val().trim();
        
        if (!requestId || !status) {
            showError('Please select a status');
            return;
        }
        
        $.ajax({
            url: '../backend/api/justification.php',
            method: 'POST',
            data: {
                action: 'review',
                request_id: requestId,
                status: status,
                review_notes: reviewNotes
            },
            success: function(response) {
                if (response.success) {
                    alert('Justification reviewed successfully');
                    $('#reviewModal').removeClass('active');
                    loadJustifications();
                } else {
                    showError(response.message || 'Failed to review justification');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error reviewing justification';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {}
                showError(errorMsg);
            }
        });
    }
    
    function filterTable() {
        const statusFilter = $('#filterStatus').val();
        
        $('#justificationsTableBody tr').each(function() {
            const $row = $(this);
            const status = $row.find('.status-badge').text().toLowerCase();
            
            const matchesStatus = !statusFilter || status.includes(statusFilter);
            $row.toggle(matchesStatus);
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
        const $error = $('#reviewError');
        $error.html(message.replace(/\n/g, '<br>')).addClass('active').show();
        setTimeout(() => {
            $error.removeClass('active');
        }, 5000);
    }
});

// Global function for modal
function openReviewModal(requestId) {
    // This will be handled by the jQuery ready function
    $(document).ready(function() {
        // Reload to get fresh data
        location.reload();
    });
}