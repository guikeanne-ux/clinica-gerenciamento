<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtService
{
    public function encode(string $userUuid): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'change-me';
        $ttl = (int) ($_ENV['JWT_TTL'] ?? 3600);

        return JWT::encode([
            'sub' => $userUuid,
            'iat' => time(),
            'exp' => time() + $ttl,
        ], $secret, 'HS256');
    }

    public function decode(string $token): object
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'change-me';
        return JWT::decode($token, new Key($secret, 'HS256'));
    }
}
