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
    
    $stmt = $conn->prepare("SELECT id, username, email, full_name, profile_picture FROM users WHERE id = ?");
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
    
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
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
function registerUser($username, $email, $password, $fullName) {
    $conn = getDBConnection();
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
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
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
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
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $fullName);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $stmt->close();
        
        // Create default settings
        $stmt = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
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

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pages/login.php');
        exit();
    }
}
?>
