<?php

declare(strict_types=1);

namespace App\Core\Support;

final class ApiResponse
{
    public static function success(
        string $message = 'Operação realizada com sucesso.',
        array $data = [],
        array $meta = [],
        array $errors = []
    ): array {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'errors' => $errors,
        ];
    }

    public static function error(
        string $message = 'Erro interno do servidor.',
        array $errors = [],
        array $meta = []
    ): array {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => $meta,
            'errors' => $errors,
        ];
    }
}
