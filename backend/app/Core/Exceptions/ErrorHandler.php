<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Support\ApiResponse;
use Throwable;

final class ErrorHandler
{
    public static function handle(Throwable $throwable, ?string $requestId = null): array
    {
        $requestId = $requestId ?? bin2hex(random_bytes(8));

        if ($throwable instanceof HttpException) {
            self::logThrowable($throwable, $requestId, false);

            return [
                'status' => $throwable->getStatusCode(),
                'body' => ApiResponse::error(
                    $throwable->getMessage(),
                    $throwable->getErrors(),
                    [
                        'request_id' => $requestId,
                        'error_code' => $throwable->getErrorCode(),
                    ]
                ),
            ];
        }

        self::logThrowable($throwable, $requestId, true);

        return [
            'status' => 500,
            'body' => ApiResponse::error(
                'Não foi possível concluir a ação agora. Tente novamente em alguns instantes.',
                [],
                [
                    'request_id' => $requestId,
                    'error_code' => ErrorCode::INTERNAL_SERVER_ERROR,
                ]
            ),
        ];
    }

    private static function logThrowable(Throwable $throwable, string $requestId, bool $unexpected): void
    {
        $logDir = dirname(__DIR__, 3) . '/storage/logs';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $payload = [
            'timestamp' => date('c'),
            'request_id' => $requestId,
            'unexpected' => $unexpected,
            'exception' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ];

        if ($throwable instanceof HttpException) {
            $payload['status_code'] = $throwable->getStatusCode();
            $payload['error_code'] = $throwable->getErrorCode();
            $payload['context'] = $throwable->getContext();
        } else {
            $payload['trace'] = $throwable->getTraceAsString();
        }

        file_put_contents(
            $logDir . '/app.log',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }
}
