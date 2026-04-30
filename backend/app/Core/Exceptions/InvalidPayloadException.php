<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class InvalidPayloadException extends HttpException
{
    public function __construct(string $message = 'Payload inválido.', array $context = [])
    {
        parent::__construct($message, 400, [], ErrorCode::INVALID_PAYLOAD, $context);
    }
}
