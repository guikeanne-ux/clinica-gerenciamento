<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Recurso não encontrado.', array $context = [])
    {
        parent::__construct($message, 404, [], ErrorCode::NOT_FOUND, $context);
    }
}
