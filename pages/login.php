<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

// Handle language change
handleLanguageChange();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = __('error_required');
    } else {
        $result = loginUser($username, $password);
        if (!empty($result['success'])) {
            header('Location: dashboard.php');
            exit();
        } elseif (!empty($result['requires_2fa'])) {
            header('Location: twofactor.php');
            exit();
        } else {
            $error = __('error_login');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title"><?php echo __('app_name'); ?></h1>
                <p><?php echo __('login_title'); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username"><?php echo __('username'); ?></label>
                    <input type="text" id="username" name="username" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password"><?php echo __('password'); ?></label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <?php echo __('login'); ?>
                </button>
            </form>
            
            <div class="auth-footer">
                <?php echo __('dont_have_account'); ?>
                <a href="register.php"><?php echo __('register'); ?></a>
            </div>
            
            <div class="auth-footer mt-2">
                <a href="?lang=sv">Svenska</a> | 
                <a href="?lang=en">English</a>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
