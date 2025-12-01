<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/theme.php';

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdminSection = in_array($currentPage, ['admin-import', 'admin-themes'], true);

// Load user settings if logged in
$userSettings = null;
$themeMode = 'light';
if ($currentUser) {
    $userSettings = getUserSettings($currentUser['id']);
    // Set language from user settings
    $lang = $userSettings['language'] ?? ($currentUser['lang'] ?? 'sv');
    $themeMode = $userSettings['theme_mode'] ?? 'light';
    changeLanguage($lang);
}

$activeTheme = getThemeForUser($currentUser['colorscheme'] ?? null);
$themeStyles = renderThemeStyles($activeTheme);
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" data-theme="<?php echo htmlspecialchars($themeMode, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <?php echo $themeStyles; ?>
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
                    <div class="nav-dropdown">
                        <button type="button" class="nav-link <?php echo $isAdminSection ? 'active' : ''; ?>" onclick="toggleDropdown('adminDropdown')">
                            <?php echo __('admin'); ?>
                        </button>
                        <div id="adminDropdown" class="dropdown dropdown-nav">
                            <a href="<?php echo BASE_URL; ?>/pages/admin-import.php" class="<?php echo $currentPage === 'admin-import' ? 'active' : ''; ?>">
                                <?php echo __('member_import'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/admin-themes.php" class="<?php echo $currentPage === 'admin-themes' ? 'active' : ''; ?>">
                                <?php echo __('themes'); ?>
                            </a>
                        </div>
                    </div>
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
