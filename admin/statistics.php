<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireRole('administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>System Statistics</h1>
            <p>Overview of attendance data and system metrics</p>
        </div>
        
        <div class="stats-overview" id="statsOverview">
            <div class="loading">Loading statistics...</div>
        </div>
        
        <div class="charts-container">
            <div class="chart-card">
                <h2>Attendance Distribution</h2>
                <canvas id="attendanceChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h2>Monthly Attendance Trend</h2>
                <canvas id="monthlyChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h2>Top Courses by Attendance Rate</h2>
                <canvas id="coursesChart"></canvas>
            </div>
        </div>
        
        <div class="stats-table-container">
            <h2>Top Courses</h2>
            <table class="stats-table" id="topCoursesTable">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Sessions</th>
                        <th>Total Records</th>
                        <th>Present</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody id="topCoursesBody">
                    <tr><td colspan="5" class="loading">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    <script src="../assets/js/admin/statistics.js"></script>
</body>
</html>

