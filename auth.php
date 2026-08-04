<?php
require_once __DIR__ . '/db.php';

function ensure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function login_user(array $user): void {
    ensure_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['display_name'] = $user['display_name'];
    $_SESSION['is_admin'] = (bool) $user['is_admin'];
}

function current_user(): ?array {
    ensure_session();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'display_name' => $_SESSION['display_name'],
        'is_admin' => $_SESSION['is_admin'],
    ];
}

function require_login(): array {
    $user = current_user();
    if ($user === null) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?redirect=' . $redirect);
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if (!$user['is_admin']) {
        http_response_code(403);
        die('管理者権限が必要です。');
    }
    return $user;
}

function logout_user(): void {
    ensure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function csrf_token(): string {
    ensure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(string $token): bool {
    ensure_session();
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
