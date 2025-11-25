<?php
// Redirect to login page or dashboard
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/pages/login.php');
}
exit();
?>
