<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Support\ApiResponse;
use Throwable;

final class ErrorHandler
{
    public static function handle(Throwable $throwable): array
    {
        if ($throwable instanceof HttpException) {
            return [
                'status' => $throwable->getStatusCode(),
                'body' => ApiResponse::error($throwable->getMessage(), $throwable->getErrors()),
            ];
        }

        return [
            'status' => 500,
            'body' => ApiResponse::error('Erro interno do servidor.'),
        ];
    }
}
