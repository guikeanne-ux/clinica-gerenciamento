<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\NotFoundException;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class DeleteScheduleEventService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function execute(string $uuid, string $actorUserUuid): void
    {
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();

        if ($event === null) {
            throw new NotFoundException('Evento da agenda não encontrado.');
        }

        $event->delete();

        $this->audit->log('schedule.events.deleted', $actorUserUuid, ['event_uuid' => $uuid]);
    }
}
