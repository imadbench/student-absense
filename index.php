<?php
/**
 * Main Index - Redirect to login or appropriate dashboard
 */
require_once 'backend/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'professor') {
        header('Location: ' . BASE_URL . '/professor/home.php');
    } elseif ($role === 'student') {
        header('Location: ' . BASE_URL . '/student/home.php');
    } elseif ($role === 'administrator') {
        header('Location: ' . BASE_URL . '/admin/home.php');
    } else {
        header('Location: ' . BASE_URL . '/login.php');
    }
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
?>