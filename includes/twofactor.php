<?php

// Simple TOTP implementation (RFC 6238) with Base32 secrets

function base32Encode(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binaryString = '';
    foreach (str_split($data) as $char) {
        $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }

    $chunks = str_split($binaryString, 5);
    $encoded = '';
    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $encoded .= $alphabet[bindec($chunk)];
    }

    return $encoded;
}

function base32Decode(string $secret): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper($secret);
    $binaryString = '';
    foreach (str_split($secret) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            continue;
        }
        $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }

    $bytes = str_split($binaryString, 8);
    $decoded = '';
    foreach ($bytes as $byte) {
        if (strlen($byte) === 8) {
            $decoded .= chr(bindec($byte));
        }
    }

    return $decoded;
}

function generateTotpSecret(int $length = 16): string
{
    return base32Encode(random_bytes($length));
}

function getTotpCode(string $secret, ?int $timestamp = null, int $digits = 6): string
{
    $timestamp = $timestamp ?? time();
    $timeSlice = (int)floor($timestamp / 30);
    $decodedSecret = base32Decode($secret);
    if ($decodedSecret === '') {
        return '';
    }

    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $time, $decodedSecret, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = (ord($hash[$offset]) & 0x7F) << 24 |
                 (ord($hash[$offset + 1]) & 0xFF) << 16 |
                 (ord($hash[$offset + 2]) & 0xFF) << 8 |
                 (ord($hash[$offset + 3]) & 0xFF);

    $code = $truncated % pow(10, $digits);
    return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
}

function verifyTotpCode(string $secret, string $code, int $window = 1): bool
{
    $code = preg_replace('/\\D/', '', $code);
    if ($code === '') {
        return false;
    }

    $timestamp = time();
    for ($i = -$window; $i <= $window; $i++) {
        $calc = getTotpCode($secret, $timestamp + ($i * 30));
        if (hash_equals($calc, $code)) {
            return true;
        }
    }

    return false;
}

function buildOtpAuthUrl(string $issuer, string $account, string $secret): string
{
    $label = rawurlencode($issuer . ':' . $account);
    $issuerParam = rawurlencode($issuer);
    return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerParam}";
}
