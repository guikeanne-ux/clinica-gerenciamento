<?php

declare(strict_types=1);

namespace App\Core\Http;

final class JsonResponse
{
    public static function make(array $payload, int $status = 200): array
    {
        return [
            'status' => $status,
            'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
    }
}
