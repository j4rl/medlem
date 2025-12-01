<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

handleLanguageChange();

if (!hasPendingTwoFactor()) {
    header('Location: login.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    if (completeTwoFactorLogin($code)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = __('twofa_invalid_code');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('twofa_title'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title"><?php echo __('app_name'); ?></h1>
                <p><?php echo __('twofa_prompt'); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="code"><?php echo __('twofa_code_label'); ?></label>
                    <input type="text" id="code" name="code" class="form-input" inputmode="numeric" autocomplete="one-time-code" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <?php echo __('submit'); ?>
                </button>
            </form>
            
            <div class="auth-footer mt-2">
                <a href="?lang=sv">Svenska</a> | 
                <a href="?lang=en">English</a>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
