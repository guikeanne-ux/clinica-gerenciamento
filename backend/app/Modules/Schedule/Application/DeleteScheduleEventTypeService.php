<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Modules\Audit\Infrastructure\Services\AuditService;

final class DeleteScheduleEventTypeService
{
    public function __construct(
        private readonly GetScheduleEventTypeService $getService = new GetScheduleEventTypeService(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $uuid, string $actorUserUuid): void
    {
        $eventType = $this->getService->execute($uuid);
        $eventType->delete();

        $this->audit->log('schedule.event_types.deleted', $actorUserUuid, ['event_type_uuid' => $uuid]);
    }
}
