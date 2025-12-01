<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';

// Get user settings (fallback defaults; table is optional)
function getUserSettings($userId) {
    // Defaults
    $defaults = [
        'theme_mode' => 'light',
        'primary_color' => defaultThemePalette()['primary_light'],
        'language' => 'sv'
    ];

    $conn = getDBConnection();
    if ($conn->query("SHOW TABLES LIKE 'user_settings'")->num_rows === 0) {
        closeDBConnection($conn);
        return $defaults;
    }

    $stmt = $conn->prepare("SELECT theme_mode, primary_color, language FROM user_settings WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    $stmt->close();
    closeDBConnection($conn);

    return $settings ?: $defaults;
}

// Update user settings (no-op if table missing)
function updateUserSettings($userId, $themeMode, $primaryColor, $language) {
    $conn = getDBConnection();
    if ($conn->query("SHOW TABLES LIKE 'user_settings'")->num_rows === 0) {
        closeDBConnection($conn);
        return true;
    }
    
    $stmt = $conn->prepare("UPDATE user_settings SET theme_mode = ?, primary_color = ?, language = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $themeMode, $primaryColor, $language, $userId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        $stmt = $conn->prepare("INSERT INTO user_settings (theme_mode, primary_color, language, user_id) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE theme_mode = VALUES(theme_mode), primary_color = VALUES(primary_color), language = VALUES(language)");
        $stmt->bind_param("sssi", $themeMode, $primaryColor, $language, $userId);
        $stmt->execute();
        $success = $stmt->affected_rows >= 0;
        $stmt->close();
    } else {
        $success = true;
    }

    closeDBConnection($conn);
    return $success;
}

// Update profile picture
function updateProfilePicture($userId, $filename) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("UPDATE tbl_users SET pic = ? WHERE id = ?");
    $stmt->bind_param("si", $filename, $userId);
    
    $success = $stmt->execute();
    
    $stmt->close();
    closeDBConnection($conn);
    
    return $success;
}

// Update user profile
function updateUserProfile($userId, $fullName, $email) {
    $conn = getDBConnection();
    
    // Check if email is already used by another user
    $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'error_email_taken'];
    }
    $stmt->close();
    
    // Update user
    $stmt = $conn->prepare("UPDATE tbl_users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fullName, $email, $userId);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => true];
    }
    
    $stmt->close();
    closeDBConnection($conn);
    return ['success' => false, 'error' => 'error_general'];
}
?>
