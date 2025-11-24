<?php
// Redirect to login page or dashboard
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
} else {
    header('Location: /pages/login.php');
}
exit();
?>
