<?php
require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    
    $stmt = $conn->prepare("SELECT id, username, email, name AS full_name, pic AS profile_picture, lang, role, userlevel FROM tbl_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    closeDBConnection($conn);
    
    return $user;
}

// Login user
function loginUser($username, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT id, username, password FROM tbl_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            $stmt->close();
            closeDBConnection($conn);
            return true;
        }
    }
    
    $stmt->close();
    closeDBConnection($conn);
    return false;
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
    $stmt = $conn->prepare("INSERT INTO tbl_users (username, email, password, name, phone, pic, lang, colorscheme, userlevel, role) VALUES (?, ?, ?, ?, ?, 'default.png', 'sv', 1, 10, 'Användare')");
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
    session_unset();
    session_destroy();
}

function isAdminUser(): bool {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }

    $role = strtolower($user['role'] ?? '');
    return $role === 'admin';
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
