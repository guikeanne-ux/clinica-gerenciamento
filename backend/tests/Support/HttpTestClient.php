<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Http\Kernel;

final class HttpTestClient
{
    public static function get(string $uri): array
    {
        return (new Kernel())->handle('GET', $uri);
    }
}
