<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class ErrorCode
{
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
    public const TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const FORBIDDEN = 'FORBIDDEN';
    public const NOT_FOUND = 'NOT_FOUND';
    public const CONFLICT = 'CONFLICT';
    public const DUPLICATE_LOGIN = 'DUPLICATE_LOGIN';
    public const DUPLICATE_DOCUMENT = 'DUPLICATE_DOCUMENT';
    public const DUPLICATE_EMAIL = 'DUPLICATE_EMAIL';
    public const BUSINESS_RULE_VIOLATION = 'BUSINESS_RULE_VIOLATION';
    public const INVALID_PAYLOAD = 'INVALID_PAYLOAD';
    public const INVALID_UPLOAD = 'INVALID_UPLOAD';
    public const UPLOAD_TOO_LARGE = 'UPLOAD_TOO_LARGE';
    public const UNSUPPORTED_FILE_TYPE = 'UNSUPPORTED_FILE_TYPE';
    public const INVALID_MIME_TYPE = 'INVALID_MIME_TYPE';
    public const RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    public const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
}
