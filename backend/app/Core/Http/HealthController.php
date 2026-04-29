<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Support\ApiResponse;

final class HealthController
{
    public function __invoke(): array
    {
        return JsonResponse::make(
            ApiResponse::success(
                message: 'Healthcheck OK.',
                data: [
                    'status' => 'ok',
                    'timestamp' => gmdate(DATE_ATOM),
                ]
            ),
            200
        );
    }
}
