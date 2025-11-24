<?php
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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        $error = __('error_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('error_email_invalid');
    } elseif ($password !== $confirmPassword) {
        $error = __('error_password_mismatch');
    } else {
        $result = registerUser($username, $email, $password, $fullName);
        if ($result['success']) {
            $success = __('success_register');
        } else {
            $error = __($result['error']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('register_title'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title"><?php echo __('app_name'); ?></h1>
                <p><?php echo __('register_title'); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="full_name"><?php echo __('full_name'); ?></label>
                    <input type="text" id="full_name" name="full_name" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="username"><?php echo __('username'); ?></label>
                    <input type="text" id="username" name="username" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email"><?php echo __('email'); ?></label>
                    <input type="email" id="email" name="email" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password"><?php echo __('password'); ?></label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password"><?php echo __('confirm_password'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <?php echo __('register'); ?>
                </button>
            </form>
            
            <div class="auth-footer">
                <?php echo __('already_have_account'); ?>
                <a href="login.php"><?php echo __('login'); ?></a>
            </div>
            
            <div class="auth-footer mt-2">
                <a href="?lang=sv">Svenska</a> | 
                <a href="?lang=en">English</a>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/app.js"></script>
</body>
</html>
