<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class ValidationException extends HttpException
{
    public function __construct(string $message = 'Erro de validação.', array $errors = [], array $context = [])
    {
        parent::__construct($message, 422, $errors, ErrorCode::VALIDATION_ERROR, $context);
    }
}
