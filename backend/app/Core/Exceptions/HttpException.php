<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

class HttpException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 500,
        private readonly array $errors = [],
        private readonly string $errorCode = 'INTERNAL_SERVER_ERROR',
        private readonly array $context = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
