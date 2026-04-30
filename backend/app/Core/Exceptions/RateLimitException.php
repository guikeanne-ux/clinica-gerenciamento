<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class RateLimitException extends HttpException
{
    public function __construct(string $message = 'Muitas tentativas. Aguarde e tente novamente.', array $context = [])
    {
        parent::__construct($message, 429, [], ErrorCode::RATE_LIMIT_EXCEEDED, $context);
    }
}
