<?php

declare(strict_types=1);

namespace App\Core\Http;

final class JsonResponse
{
    public static function make(array $payload, int $status = 200): array
    {
        $flags = JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
            | JSON_THROW_ON_ERROR;

        try {
            $body = json_encode($payload, $flags);
        } catch (\JsonException) {
            $body = json_encode(self::sanitizeUtf8($payload), $flags & ~JSON_THROW_ON_ERROR);
            if ($body === false) {
                $body = '{"success":false,"message":"Erro ao serializar resposta.","data":null,"meta":{},"errors":[]}';
                $status = 500;
            }
        }

        return [
            'status' => $status,
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
    }

    private static function sanitizeUtf8(mixed $value): mixed
    {
        if (is_string($value)) {
            if (preg_match('//u', $value) === 1) {
                return $value;
            }

            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            return $converted === false ? '' : $converted;
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[self::sanitizeUtf8($key)] = self::sanitizeUtf8($item);
            }

            return $sanitized;
        }

        return $value;
    }
}
