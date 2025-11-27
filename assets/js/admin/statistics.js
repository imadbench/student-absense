/**
 * Admin Statistics Page JavaScript
 */

$(document).ready(function() {
    let attendanceChart, monthlyChart, coursesChart;
    
    loadStatistics();
    
    function loadStatistics() {
        $.ajax({
            url: '../backend/api/statistics.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    renderOverview(response.data.overview);
                    renderAttendanceChart(response.data.attendance);
                    renderMonthlyChart(response.data.monthly || []);
                    renderCoursesChart(response.data.top_courses || []);
                    renderTopCoursesTable(response.data.top_courses || []);
                } else {
                    showError(response.message || 'Failed to load statistics');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Error loading statistics';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    console.error('Statistics error:', error);
                }
                showError(errorMsg);
                // Show empty state
                $('#statsOverview').html('<div class="loading">Error loading statistics</div>');
            }
        });
    }
    
    function renderOverview(overview) {
        $('#statsOverview').html(`
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value">${overview.total_students}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Professors</div>
                <div class="stat-value">${overview.total_professors}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Courses</div>
                <div class="stat-value">${overview.total_courses}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value">${overview.total_sessions}</div>
            </div>
        `);
    }
    
    function renderAttendanceChart(attendance) {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;
        
        if (attendanceChart) {
            attendanceChart.destroy();
        }
        
        const present = parseInt(attendance?.present || 0);
        const absent = parseInt(attendance?.absent || 0);
        const late = parseInt(attendance?.late || 0);
        const excused = parseInt(attendance?.excused || 0);
        
        // Only show chart if there's data
        if (present === 0 && absent === 0 && late === 0 && excused === 0) {
            ctx.parentElement.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">No attendance data available</p>';
            return;
        }
        
        attendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    data: [present, absent, late, excused],
                    backgroundColor: [
                        '#10b981',
                        '#ef4444',
                        '#f59e0b',
                        '#2563eb'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    function renderMonthlyChart(monthly) {
        const ctx = document.getElementById('monthlyChart');
        if (!ctx) return;
        
        if (monthlyChart) {
            monthlyChart.destroy();
        }
        
        if (!monthly || monthly.length === 0) {
            ctx.parentElement.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">No monthly data available</p>';
            return;
        }
        
        const labels = monthly.map(m => m.month || '');
        const presentData = monthly.map(m => parseInt(m.present || 0));
        const sessionsData = monthly.map(m => parseInt(m.sessions || 0));
        
        monthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Present',
                    data: presentData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Sessions',
                    data: sessionsData,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }
    
    function renderCoursesChart(courses) {
        const ctx = document.getElementById('coursesChart');
        if (!ctx) return;
        
        if (coursesChart) {
            coursesChart.destroy();
        }
        
        if (!courses || courses.length === 0) {
            ctx.parentElement.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">No course data available</p>';
            return;
        }
        
        const top5 = courses.slice(0, 5);
        const labels = top5.map(c => (c.course_name || 'Unknown').substring(0, 20));
        const rates = top5.map(c => parseFloat(c.attendance_rate || 0));
        
        coursesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: rates,
                    backgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    function renderTopCoursesTable(courses) {
        const $tbody = $('#topCoursesBody');
        $tbody.empty();
        
        if (courses.length === 0) {
            $tbody.html('<tr><td colspan="5" class="loading">No data available</td></tr>');
            return;
        }
        
        courses.forEach(function(course) {
            const $row = $('<tr>').html(`
                <td>${escapeHtml(course.course_name)}</td>
                <td>${course.sessions || 0}</td>
                <td>${course.total_records || 0}</td>
                <td>${course.present || 0}</td>
                <td>${parseFloat(course.attendance_rate || 0).toFixed(2)}%</td>
            `);
            $tbody.append($row);
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
        alert('Error: ' + message);
    }
});

