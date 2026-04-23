<?php

function appRequestIsSecure(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }

    return false;
}

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (!headers_sent()) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => appRequestIsSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_start();
}

function csrfToken(): string
{
    startAppSession();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function verifyCsrfToken(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals(csrfToken(), $token);
}

function requireCsrfToken(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verifyCsrfToken(is_string($token) ? $token : null)) {
        http_response_code(400);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $isApiScript = substr($script, -8) === '/api.php' || substr($script, -7) === 'api.php';
        if (stripos($accept, 'application/json') !== false || stripos($contentType, 'application/json') !== false || $isApiScript) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit();
        }
        exit('Invalid CSRF token.');
    }
}
