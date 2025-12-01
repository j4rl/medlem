<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/theme.php';
require_once __DIR__ . '/../includes/twofactor.php';
requireLogin();

$user = getCurrentUser();
$settings = getUserSettings($user['id']);
$themes = getAllThemes();
$currentThemeId = (int)($user['colorscheme'] ?? ($themes[0]['id'] ?? 1));
$twofa = getTwoFactorSettings($user['id']);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_settings';

    if ($action === 'update_settings') {
        $themeMode = $_POST['theme_mode'] ?? 'light';
        $language = $_POST['language'] ?? 'sv';
        $themeId = isset($_POST['theme_id']) ? (int)$_POST['theme_id'] : $currentThemeId;

        $selectedTheme = getThemeById($themeId);
        $primaryColor = $selectedTheme['primary_light'] ?? '#2563eb';
        
        $themeSaved = setUserTheme($user['id'], $themeId);
        
        if ($themeSaved && updateUserSettings($user['id'], $themeMode, $primaryColor, $language)) {
            changeLanguage($language);
            $success = __('success_update');
            $settings = getUserSettings($user['id']);
            $currentThemeId = $themeId;
        } else {
            $error = __('error_general');
        }
    } elseif ($action === 'generate_2fa') {
        $secret = generateTotpSecret();
        if (setTwoFactorSecret($user['id'], $secret)) {
            $success = __('twofa_secret_generated');
            $twofa = getTwoFactorSettings($user['id']);
        } else {
            $error = __('error_general');
        }
    } elseif ($action === 'enable_2fa') {
        $code = trim($_POST['twofa_code'] ?? '');
        $twofa = getTwoFactorSettings($user['id']);
        if (!empty($twofa['twofa_secret']) && verifyTotpCode($twofa['twofa_secret'], $code)) {
            if (enableTwoFactor($user['id'])) {
                $success = __('twofa_enabled');
                $twofa = getTwoFactorSettings($user['id']);
            } else {
                $error = __('error_general');
            }
        } else {
            $error = __('twofa_invalid_code');
        }
    } elseif ($action === 'disable_2fa') {
        $code = trim($_POST['twofa_code'] ?? '');
        $twofa = getTwoFactorSettings($user['id']);
        $secret = $twofa['twofa_secret'] ?? '';
        if ($secret === '' || verifyTotpCode($secret, $code)) {
            if (disableTwoFactor($user['id'])) {
                $success = __('twofa_disabled');
                $twofa = getTwoFactorSettings($user['id']);
            } else {
                $error = __('error_general');
            }
        } else {
            $error = __('twofa_invalid_code');
        }
    }
}

$twofaEnabled = !empty($twofa['twofa_enabled']);
$twofaSecret = $twofa['twofa_secret'] ?? null;
$otpAuthUrl = $twofaSecret ? buildOtpAuthUrl(__('app_name'), $twofa['username'] ?? $user['full_name'] ?? $user['username'], $twofaSecret) : '';

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <h1><?php echo __('settings'); ?></h1>
        
        <div class="card mt-3">
            <h2 class="card-header"><?php echo __('appearance'); ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_settings">
                <div class="form-group">
                    <label class="form-label"><?php echo __('choose_theme'); ?></label>
                    <div class="theme-grid">
                        <?php foreach ($themes as $theme): ?>
                            <label class="theme-card">
                                <input type="radio" name="theme_id" value="<?php echo $theme['id']; ?>" <?php echo (int)$currentThemeId === (int)$theme['id'] ? 'checked' : ''; ?>>
                                <div class="theme-card-body">
                                    <div class="theme-card-header">
                                        <span class="theme-name"><?php echo htmlspecialchars($theme['theme_name']); ?></span>
                                        <span class="theme-pill">#<?php echo $theme['id']; ?></span>
                                    </div>
                                    <div class="theme-swatches">
                                        <span style="background: <?php echo htmlspecialchars($theme['primary_light']); ?>" title="<?php echo __('primary_color'); ?>"></span>
                                        <span style="background: <?php echo htmlspecialchars($theme['accent_light']); ?>" title="<?php echo __('accent_color'); ?>"></span>
                                        <span style="background: <?php echo htmlspecialchars($theme['surface_light']); ?>" title="<?php echo __('surface_light'); ?>"></span>
                                        <span style="background: <?php echo htmlspecialchars($theme['bg_light']); ?>" title="<?php echo __('bg_light'); ?>"></span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo __('theme'); ?></label>
                    <div class="flex gap-2">
                        <label style="flex: 1;">
                            <input type="radio" name="theme_mode" value="light" 
                                   <?php echo $settings['theme_mode'] === 'light' ? 'checked' : ''; ?>
                                   onchange="setTheme('light')">
                            <div class="card" style="cursor: pointer; text-align: center; padding: 1rem;">
                                <div style="font-size: 2rem;">Light</div>
                                <?php echo __('light_mode'); ?>
                            </div>
                        </label>
                        <label style="flex: 1;">
                            <input type="radio" name="theme_mode" value="dark" 
                                   <?php echo $settings['theme_mode'] === 'dark' ? 'checked' : ''; ?>
                                   onchange="setTheme('dark')">
                            <div class="card" style="cursor: pointer; text-align: center; padding: 1rem;">
                                <div style="font-size: 2rem;">Dark</div>
                                <?php echo __('dark_mode'); ?>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="language"><?php echo __('language'); ?></label>
                    <select id="language" name="language" class="form-select">
                        <option value="sv" <?php echo $settings['language'] === 'sv' ? 'selected' : ''; ?>>
                            Svenska (Swedish)
                        </option>
                        <option value="en" <?php echo $settings['language'] === 'en' ? 'selected' : ''; ?>>
                            English
                        </option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo __('save'); ?>
                </button>
            </form>
        </div>

        <div class="card mt-3">
            <h2 class="card-header"><?php echo __('twofa_title'); ?></h2>
            <p class="muted"><?php echo __('twofa_description'); ?></p>

            <div class="alert <?php echo $twofaEnabled ? 'alert-success' : 'alert-warning'; ?>">
                <?php echo $twofaEnabled ? __('twofa_status_on') : __('twofa_status_off'); ?>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!$twofaEnabled): ?>
                <form method="POST" action="" class="mb-2">
                    <input type="hidden" name="action" value="generate_2fa">
                    <button type="submit" class="btn btn-secondary"><?php echo $twofaSecret ? __('twofa_regenerate') : __('twofa_generate'); ?></button>
                </form>

                <?php if ($twofaSecret): ?>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('twofa_secret_label'); ?></label>
                        <input type="text" readonly class="form-input" value="<?php echo htmlspecialchars($twofaSecret); ?>">
                        <?php if ($otpAuthUrl): ?>
                            <small class="muted"><?php echo __('twofa_otpauth_hint'); ?>: <code><?php echo htmlspecialchars($otpAuthUrl); ?></code></small>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="" class="mt-2">
                        <input type="hidden" name="action" value="enable_2fa">
                        <div class="form-group">
                            <label class="form-label"><?php echo __('twofa_code_label'); ?></label>
                            <input type="text" name="twofa_code" class="form-input" inputmode="numeric" autocomplete="one-time-code" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo __('twofa_enable'); ?></button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="disable_2fa">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('twofa_code_label'); ?></label>
                        <input type="text" name="twofa_code" class="form-input" inputmode="numeric" autocomplete="one-time-code" required>
                    </div>
                    <button type="submit" class="btn btn-secondary"><?php echo __('twofa_disable'); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
    input[type="radio"] {
        display: none;
    }
    
    input[type="radio"]:checked + .card {
        border: 2px solid var(--primary);
        background-color: var(--bg);
    }

    .theme-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
    }

    .theme-card {
        display: block;
        cursor: pointer;
    }

    .theme-card input {
        display: none;
    }

    .theme-card-body {
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 0.85rem;
        background: var(--surface);
        transition: border-color 0.2s ease, transform 0.2s ease;
    }

    .theme-card input:checked + .theme-card-body,
    .theme-card-body:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .theme-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .theme-name {
        font-weight: 600;
        color: var(--text);
    }

    .theme-pill {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 999px;
        background: var(--bg);
        border: 1px solid var(--border);
        color: var(--muted);
    }

    .theme-swatches {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.4rem;
    }

    .theme-swatches span {
        display: block;
        height: 20px;
        border-radius: 0.35rem;
        border: 1px solid rgba(0, 0, 0, 0.06);
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
