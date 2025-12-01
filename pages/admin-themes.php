<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/theme.php';

requireAdmin();

$currentUser = getCurrentUser();
$error = '';
$success = '';
$themes = getAllThemes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $themeId = isset($_POST['theme_id']) && $_POST['theme_id'] !== '' ? (int)$_POST['theme_id'] : null;

    if ($action === 'delete' && $themeId) {
        if (deleteTheme($themeId)) {
            $success = __('theme_deleted');
        } else {
            $error = __('error_general');
        }
    } else {
        $data = [
            'bg_light' => $_POST['bg_light'] ?? '',
            'bg_dark' => $_POST['bg_dark'] ?? '',
            'surface_light' => $_POST['surface_light'] ?? '',
            'surface_dark' => $_POST['surface_dark'] ?? '',
            'primary_light' => $_POST['primary_light'] ?? '',
            'primary_dark' => $_POST['primary_dark'] ?? '',
            'accent_light' => $_POST['accent_light'] ?? '',
            'accent_dark' => $_POST['accent_dark'] ?? '',
            'text_light' => $_POST['text_light'] ?? '',
            'text_dark' => $_POST['text_dark'] ?? '',
            'muted_light' => $_POST['muted_light'] ?? '',
            'muted_dark' => $_POST['muted_dark'] ?? '',
            'border_light' => $_POST['border_light'] ?? '',
            'border_dark' => $_POST['border_dark'] ?? '',
            'theme_name' => $_POST['theme_name'] ?? ''
        ];

        $result = saveTheme($data, $themeId);
        if (!empty($result['success'])) {
            $success = __('theme_saved');
        } else {
            $error = __('error_general');
        }
    }

    $themes = getAllThemes();
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <p class="eyebrow"><?php echo __('admin'); ?></p>
        <h1><?php echo __('theme_manager'); ?></h1>
        <p class="muted"><?php echo __('theme_manager_help'); ?></p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card mt-3">
            <h2 class="card-header"><?php echo __('color_themes'); ?></h2>
            <div class="theme-manager-grid">
                <?php foreach ($themes as $theme): ?>
                <form method="POST" class="theme-editor">
                    <input type="hidden" name="theme_id" value="<?php echo $theme['id']; ?>">
                    <div class="theme-editor-header">
                        <div>
                            <p class="eyebrow"><?php echo __('theme_name'); ?> #<?php echo $theme['id']; ?></p>
                            <input type="text" name="theme_name" class="form-input" value="<?php echo htmlspecialchars($theme['theme_name']); ?>" required>
                        </div>
                        <div class="theme-swatches">
                            <span style="background: <?php echo htmlspecialchars($theme['primary_light']); ?>" title="<?php echo __('primary_light'); ?>"></span>
                            <span style="background: <?php echo htmlspecialchars($theme['accent_light']); ?>" title="<?php echo __('accent_light'); ?>"></span>
                            <span style="background: <?php echo htmlspecialchars($theme['surface_light']); ?>" title="<?php echo __('surface_light'); ?>"></span>
                            <span style="background: <?php echo htmlspecialchars($theme['bg_light']); ?>" title="<?php echo __('bg_light'); ?>"></span>
                        </div>
                    </div>

                    <div class="grid grid-3 theme-fields">
                        <?php 
                        $fields = [
                            'bg_light', 'bg_dark',
                            'surface_light', 'surface_dark',
                            'primary_light', 'primary_dark',
                            'accent_light', 'accent_dark',
                            'text_light', 'text_dark',
                            'muted_light', 'muted_dark',
                            'border_light', 'border_dark'
                        ];
                        foreach ($fields as $field): ?>
                            <div class="form-group">
                                <label class="form-label" for="<?php echo $field . '_' . $theme['id']; ?>"><?php echo __( $field ); ?></label>
                                <input id="<?php echo $field . '_' . $theme['id']; ?>" type="color" class="form-input" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($theme[$field]); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex gap-2" style="margin-top: 0.75rem;">
                        <button type="submit" name="action" value="save" class="btn btn-primary btn-sm"><?php echo __('save'); ?></button>
                        <button type="submit" name="action" value="delete" class="btn btn-secondary btn-sm" onclick="return confirmDelete('<?php echo __('confirm_delete_theme'); ?>');">
                            <?php echo __('delete'); ?>
                        </button>
                    </div>
                </form>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card mt-3">
            <h2 class="card-header"><?php echo __('add_theme'); ?></h2>
            <form method="POST">
                <div class="grid grid-3 theme-fields">
                    <?php 
                    $fields = [
                        'theme_name' => '',
                        'bg_light' => '#ffffff',
                        'bg_dark' => '#000000',
                        'surface_light' => '#f3f4f6',
                        'surface_dark' => '#111827',
                        'primary_light' => '#2563eb',
                        'primary_dark' => '#60a5fa',
                        'accent_light' => '#0ea5e9',
                        'accent_dark' => '#38bdf8',
                        'text_light' => '#111827',
                        'text_dark' => '#e5e7eb',
                        'muted_light' => '#6b7280',
                        'muted_dark' => '#9ca3af',
                        'border_light' => '#e5e7eb',
                        'border_dark' => '#1f2937'
                    ];
                    foreach ($fields as $field => $default):
                    ?>
                    <div class="form-group">
                        <label class="form-label" for="new_<?php echo $field; ?>"><?php echo __($field); ?></label>
                        <input 
                            id="new_<?php echo $field; ?>" 
                            type="<?php echo $field === 'theme_name' ? 'text' : 'color'; ?>" 
                            class="form-input" 
                            name="<?php echo $field; ?>" 
                            value="<?php echo $field === 'theme_name' ? '' : $default; ?>" 
                            <?php echo $field === 'theme_name' ? 'required' : ''; ?>>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="action" value="save" class="btn btn-primary"><?php echo __('add_theme'); ?></button>
            </form>
        </div>
    </div>
</main>

<style>
    .theme-manager-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1rem;
    }

    .theme-editor {
        border: 1px solid var(--border);
        padding: 1rem;
        border-radius: 0.75rem;
        background: var(--surface);
    }

    .theme-editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.75rem;
    }

    .theme-editor .theme-swatches {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.25rem;
        min-width: 120px;
    }

    .theme-editor .theme-swatches span {
        height: 18px;
        border-radius: 0.35rem;
        border: 1px solid rgba(0, 0, 0, 0.06);
    }

    .theme-fields .form-group input[type="color"] {
        padding: 0.3rem;
        height: 40px;
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
