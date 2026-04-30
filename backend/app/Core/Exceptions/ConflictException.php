<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class ConflictException extends HttpException
{
    public function __construct(string $message = 'Conflito de dados.', string $errorCode = ErrorCode::CONFLICT, array $context = [])
    {
        parent::__construct($message, 409, [], $errorCode, $context);
    }
}
