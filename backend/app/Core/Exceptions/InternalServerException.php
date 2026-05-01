<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class InternalServerException extends HttpException
{
    public function __construct(
        string $message = 'Não foi possível concluir a ação agora. Tente novamente em alguns instantes.',
        array $context = []
    ) {
        parent::__construct($message, 500, [], ErrorCode::INTERNAL_SERVER_ERROR, $context);
    }
}
