<?php

declare(strict_types=1);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$fullPath = '/var/www' . $uriPath;

if ($uriPath !== '/' && file_exists($fullPath) && ! is_dir($fullPath)) {
    return false;
}

if (str_starts_with($uriPath, '/api/')) {
    require __DIR__ . '/index.php';
    return true;
}

if ($uriPath === '/' || $uriPath === '/index.html') {
    $frontendEntry = '/var/www/frontend/design-system.html';
    if (file_exists($frontendEntry)) {
        readfile($frontendEntry);
        return true;
    }
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not Found';
return true;
