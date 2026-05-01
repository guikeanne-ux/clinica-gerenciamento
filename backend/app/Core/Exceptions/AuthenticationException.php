<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class AuthenticationException extends HttpException
{
    public function __construct(
        string $message = 'Não autenticado.',
        string $errorCode = ErrorCode::UNAUTHORIZED,
        array $context = []
    ) {
        parent::__construct($message, 401, [], $errorCode, $context);
    }
}
