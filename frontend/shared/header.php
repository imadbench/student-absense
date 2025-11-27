<?php
require_once __DIR__ . '/../../backend/includes/auth.php';
requireLogin();

$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="main-header">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="<?php echo $current_user['role'] === 'professor' ? 'professor/home.php' : ($current_user['role'] === 'student' ? 'student/home.php' : 'admin/home.php'); ?>">
                📚 Algiers University
            </a>
        </div>
        <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="navbar-menu" id="navbarMenu">
            <?php if ($current_user['role'] === 'student'): ?>
                <li><a href="justification.php">📝 Submit Justification</a></li>
            <?php endif; ?>
            <?php if ($current_user['role'] === 'administrator'): ?>
                <li><a href="justifications.php">📋 Justification Requests</a></li>
            <?php endif; ?>
            <li class="navbar-user">
                <span class="user-info">
                    <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
                    <span class="user-role">(<?php echo ucfirst($current_user['role']); ?>)</span>
                </span>
            </li>
            <li><a href="<?php 
                // Simple solution: logout.php is always one level up from admin/professor/student folders
                // Since header.php is included from pages in these folders, we need to go up one level
                $current_script = $_SERVER['PHP_SELF'];
                if (strpos($current_script, '/admin/') !== false || 
                    strpos($current_script, '/professor/') !== false || 
                    strpos($current_script, '/student/') !== false) {
                    echo '../logout.php';
                } else {
                    echo 'logout.php';
                }
            ?>" class="btn-logout">🚪 Logout</a></li>
        </ul>
    </nav>
</header>