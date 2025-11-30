<?php
// Base URL for the application
// Adjust if you deploy under a different subdirectory
if (!defined('BASE_URL')) {
    define('BASE_URL', '/medlem');
}

// Encryption key for member data at rest (32 bytes: raw/hex/base64).
// Preferred: set the DATA_ENCRYPTION_KEY environment variable.
// Fallback: set it directly here (leave empty to disable).
if (!defined('DATA_ENCRYPTION_KEY')) {
    $envKey = getenv('DATA_ENCRYPTION_KEY');
    define('DATA_ENCRYPTION_KEY', $envKey !== false ? $envKey : '9f3c2b8a6d7e1c04b5a9f2d8c1e7a3b4096c8d2f1a7b5e3c0d4f8a2b6e1c9d73');
}
