<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/i18n.php';
requireLogin();

$user = getCurrentUser();
$settings = getUserSettings($user['id']);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $themeMode = $_POST['theme_mode'] ?? 'light';
    $primaryColor = $_POST['primary_color'] ?? '#2563eb';
    $language = $_POST['language'] ?? 'sv';
    
    if (updateUserSettings($user['id'], $themeMode, $primaryColor, $language)) {
        changeLanguage($language);
        $success = __('success_update');
        $settings = getUserSettings($user['id']);
    } else {
        $error = __('error_general');
    }
}

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
                <div class="form-group">
                    <label class="form-label"><?php echo __('theme'); ?></label>
                    <div class="flex gap-2">
                        <label style="flex: 1;">
                            <input type="radio" name="theme_mode" value="light" 
                                   <?php echo $settings['theme_mode'] === 'light' ? 'checked' : ''; ?>
                                   onchange="setTheme('light')">
                            <div class="card" style="cursor: pointer; text-align: center; padding: 1rem;">
                                <div style="font-size: 2rem;">☀️</div>
                                <?php echo __('light_mode'); ?>
                            </div>
                        </label>
                        <label style="flex: 1;">
                            <input type="radio" name="theme_mode" value="dark" 
                                   <?php echo $settings['theme_mode'] === 'dark' ? 'checked' : ''; ?>
                                   onchange="setTheme('dark')">
                            <div class="card" style="cursor: pointer; text-align: center; padding: 1rem;">
                                <div style="font-size: 2rem;">🌙</div>
                                <?php echo __('dark_mode'); ?>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('primary_color'); ?></label>
                    <div class="color-options">
                        <?php 
                        $colors = [
                            '#2563eb' => 'Blue',
                            '#7c3aed' => 'Purple',
                            '#db2777' => 'Pink',
                            '#dc2626' => 'Red',
                            '#ea580c' => 'Orange',
                            '#16a34a' => 'Green',
                            '#0891b2' => 'Cyan',
                            '#4b5563' => 'Gray'
                        ];
                        foreach ($colors as $color => $name):
                        ?>
                        <label>
                            <input type="radio" name="primary_color" value="<?php echo $color; ?>" 
                                   <?php echo $settings['primary_color'] === $color ? 'checked' : ''; ?>
                                   onchange="setPrimaryColor('<?php echo $color; ?>')"
                                   style="display: none;">
                            <div class="color-option <?php echo $settings['primary_color'] === $color ? 'active' : ''; ?>" 
                                 style="background-color: <?php echo $color; ?>;"
                                 title="<?php echo $name; ?>">
                            </div>
                        </label>
                        <?php endforeach; ?>
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
    </div>
</main>

<style>
    input[type="radio"] {
        display: none;
    }
    
    input[type="radio"]:checked + .card {
        border: 2px solid var(--primary-color);
        background-color: var(--bg-secondary);
    }
    
    input[type="radio"]:checked + .color-option {
        border-color: var(--text-primary);
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
