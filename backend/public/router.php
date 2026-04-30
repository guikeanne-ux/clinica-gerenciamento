<?php

declare(strict_types=1);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$fullPath = '/var/www' . $uriPath;

/* Servir arquivos estáticos reais (CSS, JS, imagens, fontes) */
if ($uriPath !== '/' && file_exists($fullPath) && ! is_dir($fullPath)) {
    return false;
}

/* Rotas da API vão para o backend */
if (str_starts_with($uriPath, '/api/')) {
    require __DIR__ . '/index.php';
    return true;
}

/* Toda rota não-API serve o frontend SPA */
$frontendEntry = '/var/www/frontend/index.html';
if (file_exists($frontendEntry)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($frontendEntry);
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not Found';
return true;
