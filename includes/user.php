<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';
require_once __DIR__ . '/i18n.php';

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

function getTwoFactorSettings(int $userId): array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT twofa_secret, twofa_enabled, username FROM tbl_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc() ?: ['twofa_secret' => null, 'twofa_enabled' => 0, 'username' => ''];
    $stmt->close();
    closeDBConnection($conn);
    return $data;
}

function setTwoFactorSecret(int $userId, ?string $secret): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE tbl_users SET twofa_secret = ?, twofa_enabled = 0 WHERE id = ?");
    $stmt->bind_param("si", $secret, $userId);
    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    return $success;
}

function enableTwoFactor(int $userId): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE tbl_users SET twofa_enabled = 1 WHERE id = ? AND twofa_secret IS NOT NULL");
    $stmt->bind_param("i", $userId);
    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    return $success;
}

function disableTwoFactor(int $userId): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE tbl_users SET twofa_enabled = 0, twofa_secret = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $success = $stmt->execute();
    $stmt->close();
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

// Update user (admin edit)
function updateUserAdmin(int $userId, string $email, string $fullName, string $role = 'user', int $userlevel = 10, string $phone = ''): array {
    $conn = getDBConnection();

    // Email unique check
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

    $roleNormalized = strtolower(trim($role)) === 'admin' ? 'admin' : 'user';
    $userlevel = $userlevel ?: 10;

    $stmt = $conn->prepare("UPDATE tbl_users SET email = ?, name = ?, phone = ?, role = ?, userlevel = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $email, $fullName, $phone, $roleNormalized, $userlevel, $userId);
    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);

    return ['success' => $success, 'error' => $success ? null : 'error_general'];
}

// Create user (admin)
function createUserAdmin($username, $email, $password, $fullName, $role = 'user', $lang = 'sv', $userlevel = 10, $phone = '', $colorscheme = 1) {
    $conn = getDBConnection();

    $roleNormalized = strtolower(trim($role)) === 'admin' ? 'admin' : 'user';
    $userlevel = (int)$userlevel ?: 10;

    // Check username
    $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'error_username_taken'];
    }
    $stmt->close();

    // Check email
    $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'error_email_taken'];
    }
    $stmt->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO tbl_users (username, email, password, name, phone, pic, lang, colorscheme, userlevel, role) VALUES (?, ?, ?, ?, ?, 'default.png', ?, ?, ?, ?)");
    $stmt->bind_param("sssssssis", $username, $email, $hashedPassword, $fullName, $phone, $lang, $colorscheme, $userlevel, $roleNormalized);
    $ok = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);

    return ['success' => $ok, 'error' => $ok ? null : 'error_general'];
}

// Delete user
function deleteUserById(int $userId, ?int $currentUserId = null): bool {
    if ($currentUserId && $currentUserId === $userId) {
        return false; // do not allow self-delete
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM tbl_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    return $success;
}

// Reset password
function resetUserPassword(int $userId, string $newPassword): bool {
    $conn = getDBConnection();
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE tbl_users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $userId);
    $success = $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    return $success;
}

/**
 * Import users from CSV.
 * Required headers (case-insensitive): username,email,password,name
 * Optional headers: phone,role,lang,colorscheme,userlevel
 */
function importUsersFromCsv(string $path, ?int $importedById = null, ?string $originalName = null): array {
    $required = ['username', 'email', 'password', 'name'];
    $handle = fopen($path, 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'error_upload_failed'];
    }

    $header = fgetcsv($handle, 0, ';');
    if ($header === false || count($header) === 0) {
        fclose($handle);
        return ['success' => false, 'error' => 'missing_columns'];
    }
    $normalizedHeader = array_map(function ($h) {
        return strtolower(trim($h));
    }, $header);

    $missing = array_diff($required, $normalizedHeader);
    if (!empty($missing)) {
        fclose($handle);
        return ['success' => false, 'error' => 'missing_columns', 'missing' => array_values($missing)];
    }

    $conn = getDBConnection();
    $total = $inserted = $updated = $skipped = 0;
    $errors = [];

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $total++;
        if (count($row) === 1 && trim($row[0]) === '') {
            $skipped++;
            continue;
        }

        $data = [];
        foreach ($normalizedHeader as $idx => $key) {
            $data[$key] = isset($row[$idx]) ? trim($row[$idx]) : '';
        }

        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $passwordPlain = $data['password'] ?? '';
        $name = $data['name'] ?? '';

        if ($username === '' || $email === '' || $passwordPlain === '' || $name === '') {
            $skipped++;
            continue;
        }

        $phone = $data['phone'] ?? '';
        $role = $data['role'] ?? 'Användare';
        $lang = $data['lang'] ?? 'sv';
        $colorscheme = $data['colorscheme'] ?? 1;
        $userlevel = (int)($data['userlevel'] ?? 10);

        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $userId = (int)$exists['id'];
            $sql = "UPDATE tbl_users SET email = ?, name = ?, phone = ?, role = ?, lang = ?, colorscheme = ?, userlevel = ?";
            $params = [$email, $name, $phone, $role, $lang, $colorscheme, $userlevel];
            $types = "ssssssi";

            if ($passwordPlain !== '') {
                $hashed = password_hash($passwordPlain, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $params[] = $hashed;
                $types .= "s";
            }
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            $types .= "i";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $updated++;
            } else {
                $errors[] = "Row {$total}: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $hashedPassword = password_hash($passwordPlain, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO tbl_users (username, email, password, name, phone, pic, lang, colorscheme, userlevel, role) VALUES (?, ?, ?, ?, ?, 'default.png', ?, ?, ?, ?)");
            $stmt->bind_param("sssssssis", $username, $email, $hashedPassword, $name, $phone, $lang, $colorscheme, $userlevel, $role);
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errors[] = "Row {$total}: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    fclose($handle);
    closeDBConnection($conn);

    return [
        'success' => empty($errors),
        'total' => $total,
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors,
        'missing' => [],
        'filename' => $originalName,
    ];
}
?>
