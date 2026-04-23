<?php
// Base URL for the application
// Adjust if you deploy under a different subdirectory
if (!defined('BASE_URL')) {
    define('BASE_URL', '/medlem');
}

// Optional local secrets file for development/installed instances.
// This file must stay out of version control.
$localSecretsFile = __DIR__ . '/secrets.local.php';
if (file_exists($localSecretsFile)) {
    require_once $localSecretsFile;
}

// Encryption key for member data at rest (32 bytes: raw/hex/base64).
// Set DATA_ENCRYPTION_KEY in the environment. Leave empty to disable imports
// and avoid shipping a reusable encryption key with the codebase.
if (!defined('DATA_ENCRYPTION_KEY')) {
    $envKey = getenv('DATA_ENCRYPTION_KEY');
    define('DATA_ENCRYPTION_KEY', $envKey !== false ? $envKey : '');
}
