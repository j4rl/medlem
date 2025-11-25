<?php
// i18n helper functions
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'sv';
}

// Load language file
function loadLanguage($lang = null) {
    if ($lang) {
        $_SESSION['language'] = $lang;
    }
    
    $currentLang = $_SESSION['language'];
    $langFile = __DIR__ . '/../lang/' . $currentLang . '.php';
    
    if (file_exists($langFile)) {
        return require $langFile;
    }
    
    // Fallback to Swedish
    return require __DIR__ . '/../lang/sv.php';
}

// Get translation
function __($key) {
    static $translations = null;
    
    if ($translations === null) {
        $translations = loadLanguage();
    }
    
    return $translations[$key] ?? $key;
}

// Change language
function changeLanguage($lang) {
    $validLangs = ['sv', 'en'];
    if (in_array($lang, $validLangs)) {
        $_SESSION['language'] = $lang;
        return true;
    }
    return false;
}

// Get current language
function getCurrentLanguage() {
    return $_SESSION['language'] ?? 'sv';
}

// Handle language change from GET parameter
function handleLanguageChange() {
    if (isset($_GET['lang'])) {
        changeLanguage($_GET['lang']);
        // Redirect to remove the lang parameter
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $uri);
        exit();
    }
}
?>
