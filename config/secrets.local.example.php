<?php
// Copy to config/secrets.local.php for local development or installed instances.
// Prefer setting DATA_ENCRYPTION_KEY in the server environment for production.
if (!defined('DATA_ENCRYPTION_KEY')) {
    define('DATA_ENCRYPTION_KEY', 'put-your-32-byte-raw-64-char-hex-or-base64-key-here');
}
