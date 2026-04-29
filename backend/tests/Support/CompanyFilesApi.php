<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Http\Kernel;

final class CompanyFilesApi
{
    public static function call(string $method, string $uri, array $body = [], array $headers = []): array
    {
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower($k)] = $v;
        }

        return (new Kernel())->handle($method, $uri, $normalized, $body);
    }

    public static function loginToken(string $login = 'admin', string $password = 'admin123'): string
    {
        $res = self::call('POST', '/api/v1/auth/login', [
            'login' => $login,
            'password' => $password,
        ]);

        return json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR)['data']['token'];
    }
}
