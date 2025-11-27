<?php
require_once 'backend/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'professor') {
        header('Location: ' . BASE_URL . '/professor/home.php');
    } elseif ($role === 'student') {
        header('Location: ' . BASE_URL . '/student/home.php');
    } elseif ($role === 'administrator') {
        header('Location: ' . BASE_URL . '/admin/home.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Attendance System</title>
    <link rel="stylesheet" href="/student%20apsence/assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="university-icon">📚</div>
                <h1>Algiers University</h1>
                <h2>Attendance Management System</h2>
            </div>
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required autocomplete="username" placeholder="Enter your username or email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Enter your password">
                </div>
                <div id="loginError" class="error-message"></div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            <div class="login-footer">
                <p>Default Admin: admin / admin123</p>
                <p>© <?php echo date('Y'); ?> Algiers University. All rights reserved.</p>
            </div>
        </div>
    </div>
    <script src="/student%20apsence/assets/js/login.js"></script>
</body>
</html>