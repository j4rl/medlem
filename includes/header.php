<?php
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Load user settings if logged in
$userSettings = null;
if ($currentUser) {
    $userSettings = getUserSettings($currentUser['id']);
    // Set language from user settings
    if (isset($userSettings['language'])) {
        changeLanguage($userSettings['language']);
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php if ($currentUser): ?>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/pages/dashboard.php" class="logo"><?php echo __('app_name'); ?></a>
                <nav class="nav">
                    <a href="/pages/dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <?php echo __('dashboard'); ?>
                    </a>
                    <a href="/pages/cases.php" class="<?php echo $currentPage === 'cases' ? 'active' : ''; ?>">
                        <?php echo __('cases'); ?>
                    </a>
                    <div class="user-menu">
                        <img src="/assets/uploads/profiles/<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" 
                             alt="<?php echo htmlspecialchars($currentUser['full_name']); ?>" 
                             class="user-avatar"
                             onclick="toggleDropdown('userDropdown')"
                             onerror="this.src='/assets/images/default.png'">
                        <div id="userDropdown" class="dropdown">
                            <a href="/pages/profile.php"><?php echo __('my_profile'); ?></a>
                            <a href="/pages/settings.php"><?php echo __('settings'); ?></a>
                            <a href="/pages/logout.php"><?php echo __('logout'); ?></a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>
    <?php endif; ?>
    
    <script>
        // Apply user theme and color settings
        <?php if ($userSettings): ?>
        document.documentElement.setAttribute('data-theme', '<?php echo $userSettings['theme_mode']; ?>');
        document.documentElement.style.setProperty('--primary-color', '<?php echo $userSettings['primary_color']; ?>');
        <?php endif; ?>
    </script>
