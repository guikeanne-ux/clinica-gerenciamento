<?php

declare(strict_types=1);

use App\Core\Http\Kernel;

require dirname(__DIR__) . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$headers = [];

foreach (getallheaders() as $key => $value) {
    $headers[strtolower($key)] = $value;
}

$raw = file_get_contents('php://input') ?: '';
$body = [];
if ($raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

$response = (new Kernel())->handle($method, $uri, $headers, $body);
http_response_code($response['status']);

foreach ($response['headers'] as $headerName => $headerValue) {
    header($headerName . ': ' . $headerValue);
}

echo $response['body'];
