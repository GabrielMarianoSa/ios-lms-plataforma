<?php

declare(strict_types=1);

// Railway/Docker: PHP built-in server router.
// This script is executed on every request (when passed as router file).

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';
$uriPath = '/' . ltrim($uriPath, '/');

// Block direct access to sensitive folders/files.
$blockedPrefixes = [
    '/config/',
    '/sql/',
    '/docs/',
];

foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($uriPath, $prefix)) {
        http_response_code(404);
        exit;
    }
}

$blockedExts = [
    'sql', 'md', 'env', 'bak', 'tmp', 'log', 'yml', 'yaml',
];

$ext = strtolower(pathinfo($uriPath, PATHINFO_EXTENSION));
if ($ext !== '' && in_array($ext, $blockedExts, true)) {
    http_response_code(404);
    exit;
}

// Let the server handle existing static files (assets/uploads/images/etc).
$fullPath = __DIR__ . $uriPath;
if ($uriPath !== '/' && is_file($fullPath)) {
    return false;
}

// Default: route to the requested PHP file if it exists, otherwise fall back to index.php.
$index = __DIR__ . '/index.php';

if ($uriPath !== '/' && str_ends_with($uriPath, '.php')) {
    $phpFile = __DIR__ . $uriPath;
    if (is_file($phpFile)) {
        require $phpFile;
        return;
    }
}

require $index;
