<?php
require_once __DIR__ . '/auth.php';

$slug = $_GET['app'] ?? '';
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    die('Not Found');
}

$path = __DIR__ . '/apps/' . $slug . '/index.html';
if (!is_file($path)) {
    http_response_code(404);
    die('Not Found');
}

require_login();

header('Content-Type: text/html; charset=UTF-8');
readfile($path);
