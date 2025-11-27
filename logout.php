<?php
require_once 'backend/config/config.php';
require_once 'backend/includes/auth.php';

logout();
header('Location: ' . BASE_URL . '/login.php');
exit;
?>