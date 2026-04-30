<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class BusinessRuleException extends HttpException
{
    public function __construct(string $message = 'Regra de negócio violada.', array $context = [])
    {
        parent::__construct($message, 409, [], ErrorCode::BUSINESS_RULE_VIOLATION, $context);
    }
}
