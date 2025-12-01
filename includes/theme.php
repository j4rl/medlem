<?php
require_once __DIR__ . '/../config/database.php';

function themeTableExists(mysqli $conn): bool
{
    $result = $conn->query("SHOW TABLES LIKE 'tbl_colors'");
    return $result && $result->num_rows > 0;
}

function ensureThemeTable(mysqli $conn): bool
{
    if (themeTableExists($conn)) {
        return true;
    }

    $sql = "CREATE TABLE IF NOT EXISTS tbl_colors (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        bg_light VARCHAR(100) NOT NULL,
        bg_dark VARCHAR(100) NOT NULL,
        surface_light VARCHAR(100) NOT NULL,
        surface_dark VARCHAR(100) NOT NULL,
        primary_light VARCHAR(100) NOT NULL,
        primary_dark VARCHAR(100) NOT NULL,
        accent_light VARCHAR(100) NOT NULL,
        accent_dark VARCHAR(100) NOT NULL,
        text_light VARCHAR(100) NOT NULL,
        text_dark VARCHAR(100) NOT NULL,
        muted_light VARCHAR(100) NOT NULL,
        muted_dark VARCHAR(100) NOT NULL,
        border_light VARCHAR(100) NOT NULL,
        border_dark VARCHAR(100) NOT NULL,
        theme_name VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return $conn->query($sql) === true;
}

function defaultThemePalette(): array
{
    return [
        'id' => 1,
        'bg_light' => '#f6f7fb',
        'bg_dark' => '#0b1220',
        'surface_light' => '#ffffff',
        'surface_dark' => '#111827',
        'primary_light' => '#2563eb',
        'primary_dark' => '#60a5fa',
        'accent_light' => '#0ea5e9',
        'accent_dark' => '#38bdf8',
        'text_light' => '#0f172a',
        'text_dark' => '#e5e7eb',
        'muted_light' => '#6b7280',
        'muted_dark' => '#9ca3af',
        'border_light' => '#e5e7eb',
        'border_dark' => '#1f2937',
        'theme_name' => 'Nordic Blue'
    ];
}

function normalizeColor(string $value, string $fallback): string
{
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    if (!preg_match('/^#?[0-9a-fA-F]{3,8}$/', $value)) {
        return $fallback;
    }

    return $value[0] === '#' ? $value : '#' . $value;
}

function getAllThemes(): array
{
    $conn = getDBConnection();
    if (!themeTableExists($conn)) {
        closeDBConnection($conn);
        return [defaultThemePalette()];
    }

    $themes = [];
    $result = $conn->query("SELECT id, bg_light, bg_dark, surface_light, surface_dark, primary_light, primary_dark, accent_light, accent_dark, text_light, text_dark, muted_light, muted_dark, border_light, border_dark, theme_name FROM tbl_colors ORDER BY id ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $themes[] = $row;
        }
    }

    closeDBConnection($conn);
    return $themes ?: [defaultThemePalette()];
}

function getThemeById(?int $themeId): array
{
    $themes = getAllThemes();
    if ($themeId !== null) {
        foreach ($themes as $theme) {
            if ((int)$theme['id'] === (int)$themeId) {
                return $theme;
            }
        }
    }

    return $themes[0];
}

function getThemeForUser(?int $themeId): array
{
    return getThemeById($themeId);
}

function setUserTheme(int $userId, int $themeId): bool
{
    $conn = getDBConnection();
    if (!ensureThemeTable($conn)) {
        closeDBConnection($conn);
        return false;
    }

    $themes = getAllThemes();
    $validIds = array_map(fn($theme) => (int)$theme['id'], $themes);
    $targetThemeId = in_array($themeId, $validIds, true) ? $themeId : ($validIds[0] ?? 1);

    $stmt = $conn->prepare("UPDATE tbl_users SET colorscheme = ? WHERE id = ?");
    $stmt->bind_param("ii", $targetThemeId, $userId);
    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);

    return $success;
}

function saveTheme(array $data, ?int $id = null): array
{
    $conn = getDBConnection();
    if (!ensureThemeTable($conn)) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Table creation failed'];
    }

    $defaults = defaultThemePalette();
    $fields = [
        'bg_light', 'bg_dark',
        'surface_light', 'surface_dark',
        'primary_light', 'primary_dark',
        'accent_light', 'accent_dark',
        'text_light', 'text_dark',
        'muted_light', 'muted_dark',
        'border_light', 'border_dark',
        'theme_name'
    ];

    $values = [];
    foreach ($fields as $field) {
        if ($field === 'theme_name') {
            $values[$field] = trim($data[$field] ?? $defaults[$field]);
            continue;
        }
        $values[$field] = normalizeColor($data[$field] ?? '', $defaults[$field]);
    }

    if ($values['theme_name'] === '') {
        $values['theme_name'] = $defaults['theme_name'];
    }

    if ($id) {
        $sql = "UPDATE tbl_colors SET bg_light = ?, bg_dark = ?, surface_light = ?, surface_dark = ?, primary_light = ?, primary_dark = ?, accent_light = ?, accent_dark = ?, text_light = ?, text_dark = ?, muted_light = ?, muted_dark = ?, border_light = ?, border_dark = ?, theme_name = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            str_repeat('s', 15) . 'i',
            $values['bg_light'],
            $values['bg_dark'],
            $values['surface_light'],
            $values['surface_dark'],
            $values['primary_light'],
            $values['primary_dark'],
            $values['accent_light'],
            $values['accent_dark'],
            $values['text_light'],
            $values['text_dark'],
            $values['muted_light'],
            $values['muted_dark'],
            $values['border_light'],
            $values['border_dark'],
            $values['theme_name'],
            $id
        );
    } else {
        $sql = "INSERT INTO tbl_colors (bg_light, bg_dark, surface_light, surface_dark, primary_light, primary_dark, accent_light, accent_dark, text_light, text_dark, muted_light, muted_dark, border_light, border_dark, theme_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            str_repeat('s', 15),
            $values['bg_light'],
            $values['bg_dark'],
            $values['surface_light'],
            $values['surface_dark'],
            $values['primary_light'],
            $values['primary_dark'],
            $values['accent_light'],
            $values['accent_dark'],
            $values['text_light'],
            $values['text_dark'],
            $values['muted_light'],
            $values['muted_dark'],
            $values['border_light'],
            $values['border_dark'],
            $values['theme_name']
        );
    }

    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);

    return ['success' => $success];
}

function deleteTheme(int $id): bool
{
    $conn = getDBConnection();
    if (!themeTableExists($conn)) {
        closeDBConnection($conn);
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM tbl_colors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    return $success;
}

function shadeColor(string $color, int $amount): string
{
    $hex = ltrim($color, '#');
    if (strlen($hex) === 3) {
        $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
    }

    if (!ctype_xdigit($hex) || strlen($hex) < 6) {
        return $color;
    }

    $num = hexdec($hex);
    $r = max(0, min(255, (($num >> 16) & 0xFF) + $amount));
    $g = max(0, min(255, (($num >> 8) & 0xFF) + $amount));
    $b = max(0, min(255, ($num & 0xFF) + $amount));

    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function renderThemeStyles(array $theme): string
{
    $primaryHoverLight = shadeColor($theme['primary_light'], -12);
    $primaryHoverDark = shadeColor($theme['primary_dark'], 12);

    $vars = [
        'bg_light', 'bg_dark',
        'surface_light', 'surface_dark',
        'primary_light', 'primary_dark',
        'accent_light', 'accent_dark',
        'text_light', 'text_dark',
        'muted_light', 'muted_dark',
        'border_light', 'border_dark'
    ];

    $linesRoot = [];
    foreach ($vars as $var) {
        $value = htmlspecialchars($theme[$var] ?? '', ENT_QUOTES, 'UTF-8');
        $linesRoot[] = "    --{$var}: {$value};";
    }

    $rootBlock = implode("\n", $linesRoot);

    return <<<CSS
<style>
:root {
{$rootBlock}
    --bg: var(--bg_light);
    --surface: var(--surface_light);
    --primary: var(--primary_light);
    --primary-hover: {$primaryHoverLight};
    --accent: var(--accent_light);
    --text: var(--text_light);
    --muted: var(--muted_light);
    --border: var(--border_light);
}
[data-theme="dark"] {
    --bg: var(--bg_dark);
    --surface: var(--surface_dark);
    --primary: var(--primary_dark);
    --primary-hover: {$primaryHoverDark};
    --accent: var(--accent_dark);
    --text: var(--text_dark);
    --muted: var(--muted_dark);
    --border: var(--border_dark);
}
</style>
CSS;
}
