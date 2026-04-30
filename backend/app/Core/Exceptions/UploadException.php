<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class UploadException extends HttpException
{
    public function __construct(string $message = 'Upload inválido.', string $errorCode = ErrorCode::INVALID_UPLOAD, array $context = [])
    {
        parent::__construct($message, 422, [], $errorCode, $context);
    }
}
