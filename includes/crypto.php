<?php
// Encryption helper for protecting member data at rest.
// Uses AES-256-GCM with a 12-byte IV; payload stored as base64(iv|tag|ciphertext).

/**
 * Resolve the 32-byte encryption key from an env var or constant.
 * Accepts raw 32-byte strings, hex (64 chars), or base64-encoded keys.
 */
function getEncryptionKey()
{
    $candidate = getenv('DATA_ENCRYPTION_KEY');
    if (defined('DATA_ENCRYPTION_KEY') && DATA_ENCRYPTION_KEY) {
        $candidate = DATA_ENCRYPTION_KEY;
    }

    if (!$candidate) {
        return null;
    }

    // Base64 input
    $decoded = base64_decode($candidate, true);
    if ($decoded !== false && strlen($decoded) === 32) {
        return $decoded;
    }

    // Hex input
    if (ctype_xdigit($candidate) && strlen($candidate) === 64) {
        $decoded = hex2bin($candidate);
        if ($decoded !== false && strlen($decoded) === 32) {
            return $decoded;
        }
    }

    // Raw 32-byte string
    if (strlen($candidate) === 32) {
        return $candidate;
    }

    return null;
}

function hasAesGcmSupport(): bool
{
    $methods = array_map('strtolower', openssl_get_cipher_methods());
    return in_array('aes-256-gcm', $methods, true);
}

function encryptionIsConfigured(): bool
{
    return getEncryptionKey() !== null && hasAesGcmSupport();
}

function encryptValue(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $key = getEncryptionKey();
    if (!$key) {
        throw new RuntimeException('Encryption key is not configured (set DATA_ENCRYPTION_KEY).');
    }
    if (!hasAesGcmSupport()) {
        throw new RuntimeException('AES-256-GCM is not supported by OpenSSL on this system.');
    }

    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($ciphertext === false) {
        throw new RuntimeException('Failed to encrypt value.');
    }

    return base64_encode($iv . $tag . $ciphertext);
}

function decryptValue(?string $encoded): ?string
{
    if ($encoded === null || $encoded === '') {
        return null;
    }

    $key = getEncryptionKey();
    if (!$key) {
        throw new RuntimeException('Encryption key is not configured (set DATA_ENCRYPTION_KEY).');
    }
    if (!hasAesGcmSupport()) {
        throw new RuntimeException('AES-256-GCM is not supported by OpenSSL on this system.');
    }

    $payload = base64_decode($encoded, true);
    if ($payload === false || strlen($payload) < 12 + 16) {
        return null;
    }

    $iv = substr($payload, 0, 12);
    $tag = substr($payload, 12, 16);
    $ciphertext = substr($payload, 28);

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    return $plaintext === false ? null : $plaintext;
}
