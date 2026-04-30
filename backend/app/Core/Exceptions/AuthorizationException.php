<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class AuthorizationException extends HttpException
{
    public function __construct(string $message = 'Acesso negado.', array $context = [])
    {
        parent::__construct($message, 403, [], ErrorCode::FORBIDDEN, $context);
    }
}
