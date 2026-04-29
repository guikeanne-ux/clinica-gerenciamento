<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Services;

use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Models\AuditLog;

final class AuditService
{
    public function log(string $event, ?string $userUuid, array $payload = []): void
    {
        AuditLog::query()->create([
            'uuid' => Uuid::v4(),
            'event' => $event,
            'user_uuid' => $userUuid,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
