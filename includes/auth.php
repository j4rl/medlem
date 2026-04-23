<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/twofactor.php';

startAppSession();
requireCsrfToken();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT id, username, email, name AS full_name, COALESCE(NULLIF(pic, ''), 'default.png') AS profile_picture, lang, userlevel, colorscheme, twofa_enabled, last_login FROM tbl_users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    closeDBConnection($conn);
    
    if ($user && empty($user['profile_picture'])) {
        $user['profile_picture'] = 'default.png';
    }

    return $user;
}

function getLastLoginAt(): ?string {
    return $_SESSION['last_login_at'] ?? null;
}

function getUserLevelValue(?array $user): int {
    if (!$user) {
        return 0;
    }
    return (int)($user['userlevel'] ?? 0);
}

function userIsStandard(?array $user): bool {
    $level = getUserLevelValue($user);
    return $level >= 10 && $level < 100;
}

function userHasAdminAccess(?array $user): bool {
    $level = getUserLevelValue($user);
    return $level >= 1000;
}

function stampLastLogin(int $userId): void {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE tbl_users SET last_login = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
    closeDBConnection($conn);
}

// Login user (returns ['success'=>bool, 'requires_2fa'=>bool])
function loginUser($username, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT id, username, password, twofa_enabled, twofa_secret, last_login FROM tbl_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $previousLogin = $user['last_login'] ?? null;
        
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $requires2fa = !empty($user['twofa_enabled']) && !empty($user['twofa_secret']);
            if ($requires2fa) {
                $_SESSION['pending_2fa_user'] = (int)$user['id'];
                $_SESSION['pending_2fa_username'] = $user['username'];
                $_SESSION['pending_last_login_at'] = $previousLogin;
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_login_at'] = $previousLogin;
                stampLastLogin((int)$user['id']);
            }
            
            $stmt->close();
            closeDBConnection($conn);
            return ['success' => !$requires2fa, 'requires_2fa' => $requires2fa];
        }
    }
    
    $stmt->close();
    closeDBConnection($conn);
    return ['success' => false, 'requires_2fa' => false];
}

// Register user
function registerUser($username, $email, $password, $fullName, $phone = '') {
    $conn = getDBConnection();
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'error_username_taken'];
    }
    $stmt->close();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'error_email_taken'];
    }
    $stmt->close();
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO tbl_users (username, email, password, name, phone, pic, lang, colorscheme, userlevel) VALUES (?, ?, ?, ?, ?, 'default.png', 'sv', 1, 10)");
    $stmt->bind_param("sssss", $username, $email, $hashedPassword, $fullName, $phone);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => true];
    }
    
    $stmt->close();
    closeDBConnection($conn);
    return ['success' => false, 'error' => 'error_general'];
}

// Logout user
function logoutUser() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_unset();
    session_destroy();
}

function hasPendingTwoFactor(): bool
{
    return isset($_SESSION['pending_2fa_user']);
}

function completeTwoFactorLogin(string $code): bool
{
    if (!hasPendingTwoFactor()) {
        return false;
    }

    $userId = (int)$_SESSION['pending_2fa_user'];
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, twofa_secret, last_login FROM tbl_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    closeDBConnection($conn);

    if (!$user || empty($user['twofa_secret'])) {
        return false;
    }

    if (!verifyTotpCode($user['twofa_secret'], $code)) {
        return false;
    }

    session_regenerate_id(true);
    $previousLogin = $_SESSION['pending_last_login_at'] ?? ($user['last_login'] ?? null);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['last_login_at'] = $previousLogin;
    stampLastLogin((int)$user['id']);
    unset($_SESSION['pending_2fa_user'], $_SESSION['pending_2fa_username']);
    unset($_SESSION['pending_last_login_at']);

    return true;
}

function isAdminUser(): bool {
    $user = getCurrentUser();
    return userHasAdminAccess($user);
}

function requireAdmin() {
    requireLogin();
    if (!isAdminUser()) {
        http_response_code(403);
        exit('Access denied.');
    }
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        $base = defined('BASE_URL') ? BASE_URL : '';
        header('Location: ' . rtrim($base, '/') . '/pages/login.php');
        exit();
    }
}
?>
