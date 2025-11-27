/**
 * Professor Attendance Summary Page JavaScript
 */

$(document).ready(function() {
    loadSummary();
    
    $('#btnExport').on('click', function() {
        exportToExcel();
    });
    
    $('#btnPrint').on('click', function() {
        window.print();
    });
    
    function loadSummary() {
        $.ajax({
            url: 'backend/api/attendance.php',
            method: 'GET',
            data: { action: 'get_summary', course_id: COURSE_ID },
            success: function(response) {
                if (response.success) {
                    renderSummary(response.data);
                } else {
                    showError(response.message || 'Failed to load summary');
                }
            },
            error: function() {
                showError('Error loading summary');
            }
        });
    }
    
    function renderSummary(data) {
        const summary = data.summary;
        const sessions = data.sessions;
        
        // Render course info
        renderCourseInfo();
        
        // Render statistics
        renderStats(summary);
        
        // Render table header with session numbers
        renderTableHeader(sessions);
        
        // Render table body
        renderTableBody(summary, sessions);
    }
    
    function renderCourseInfo() {
        // This would need course data - simplified for now
        $('#courseInfo').html('<p>Loading course information...</p>');
    }
    
    function renderStats(summary) {
        let totalPresent = 0;
        let totalAbsent = 0;
        let totalLate = 0;
        let totalExcused = 0;
        
        summary.forEach(function(student) {
            totalPresent += student.total_present;
            totalAbsent += student.total_absent;
            totalLate += student.total_late;
            totalExcused += student.total_excused;
        });
        
        $('#summaryStats').html(`
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value">${summary.length}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Present</div>
                <div class="stat-value">${totalPresent}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Absent</div>
                <div class="stat-value">${totalAbsent}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Late</div>
                <div class="stat-value">${totalLate}</div>
            </div>
        `);
    }
    
    function renderTableHeader(sessions) {
        const $thead = $('#summaryTableHead');
        $thead.empty();
        
        let headerHtml = '<tr><th>Student ID</th><th>Name</th>';
        
        sessions.forEach(function(session) {
            headerHtml += `<th>S${session.session_number}</th>`;
        });
        
        headerHtml += '<th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>Rate</th></tr>';
        $thead.html(headerHtml);
    }
    
    function renderTableBody(summary, sessions) {
        const $tbody = $('#summaryTableBody');
        $tbody.empty();
        
        summary.forEach(function(student) {
            let rowHtml = `
                <tr>
                    <td>${escapeHtml(student.student_id)}</td>
                    <td>${escapeHtml(student.name)}</td>
            `;
            
            student.sessions.forEach(function(session) {
                const statusClass = `status-${session.status}`;
                rowHtml += `<td><span class="status-badge ${statusClass}">${session.status.charAt(0).toUpperCase()}</span></td>`;
            });
            
            const total = student.total_present + student.total_absent + student.total_late + student.total_excused;
            const rate = total > 0 ? Math.round((student.total_present / total) * 100) : 0;
            
            rowHtml += `
                <td>${student.total_present}</td>
                <td>${student.total_absent}</td>
                <td>${student.total_late}</td>
                <td>${student.total_excused}</td>
                <td>${rate}%</td>
            </tr>`;
            
            $tbody.append(rowHtml);
        });
    }
    
    function exportToExcel() {
        // Create CSV content
        let csv = 'Student ID,Name,';
        
        // Add session headers
        const sessions = [];
        $('#summaryTableHead th').each(function(index) {
            if (index > 1 && index < $('#summaryTableHead th').length - 5) {
                csv += $(this).text() + ',';
                sessions.push($(this).text());
            }
        });
        
        csv += 'Present,Absent,Late,Excused,Rate\n';
        
        // Add data rows
        $('#summaryTableBody tr').each(function() {
            const $row = $(this);
            const cells = [];
            $row.find('td').each(function(index) {
                if (index === 0 || index === 1) {
                    cells.push($(this).text().trim());
                } else if (index < $row.find('td').length - 5) {
                    cells.push($(this).find('.status-badge').text().trim());
                } else {
                    cells.push($(this).text().trim());
                }
            });
            csv += cells.join(',') + '\n';
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `attendance_summary_${COURSE_ID}_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
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
        alert('Error: ' + message);
    }
});

