<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Http\Kernel;

final class ApiRequester
{
    public static function call(string $method, string $uri, array $body = [], array $headers = []): array
    {
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower($k)] = $v;
        }

        return (new Kernel())->handle($method, $uri, $normalized, $body);
    }
}
