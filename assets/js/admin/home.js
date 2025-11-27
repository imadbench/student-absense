/**
 * Admin Home Page JavaScript
 */

$(document).ready(function() {
    loadQuickStats();
    
    // Mobile menu toggle
    $('#navbarToggle').on('click', function() {
        $('#navbarMenu').toggleClass('active');
    });
    
    function loadQuickStats() {
        $.ajax({
            url: '../backend/api/statistics.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    renderQuickStats(response.data.overview);
                }
            },
            error: function() {
                $('#quickStats').html('<div class="loading">Error loading statistics</div>');
            }
        });
    }
    
    function renderQuickStats(overview) {
        $('#quickStats').html(`
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
                <div class="stat-label">Pending Justifications</div>
                <div class="stat-value">${overview.pending_justifications}</div>
            </div>
        `);
    }
});

