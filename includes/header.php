<?php
require_once __DIR__ . '/../config/config.php';
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
    $lang = $userSettings['language'] ?? ($currentUser['lang'] ?? 'sv');
    changeLanguage($lang);
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php if ($currentUser): ?>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="logo"><?php echo __('app_name'); ?></a>
                <nav class="nav">
                <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <?php echo __('dashboard'); ?>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/members.php" class="<?php echo $currentPage === 'members' ? 'active' : ''; ?>">
                    <?php echo __('members'); ?>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/cases.php" class="<?php echo $currentPage === 'cases' ? 'active' : ''; ?>">
                    <?php echo __('cases'); ?>
                </a>
                <?php if (!empty($currentUser['role']) && strtolower($currentUser['role']) === 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/admin-import.php" class="<?php echo $currentPage === 'admin-import' ? 'active' : ''; ?>">
                        <?php echo __('admin'); ?>
                    </a>
                <?php endif; ?>
                <div class="user-menu">
                    <img src="<?php echo BASE_URL; ?>/assets/uploads/profiles/<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" 
                         alt="<?php echo htmlspecialchars($currentUser['full_name']); ?>" 
                             class="user-avatar"
                             onclick="toggleDropdown('userDropdown')"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default.png'">
                        <div id="userDropdown" class="dropdown">
                            <a href="<?php echo BASE_URL; ?>/pages/profile.php"><?php echo __('my_profile'); ?></a>
                            <a href="<?php echo BASE_URL; ?>/pages/settings.php"><?php echo __('settings'); ?></a>
                            <a href="<?php echo BASE_URL; ?>/pages/logout.php"><?php echo __('logout'); ?></a>
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
