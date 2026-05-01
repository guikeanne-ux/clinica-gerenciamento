<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\NotFoundException;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class MarkScheduleEventDoneService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function execute(string $eventUuid, string $actorUserUuid): ScheduleEvent
    {
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $eventUuid)->whereNull('deleted_at')->first();
        if ($event === null) {
            throw new NotFoundException('Evento da agenda não encontrado.');
        }

        if ($event->status === 'cancelado') {
            throw new BusinessRuleException('Evento cancelado não pode ser marcado como realizado.');
        }

        $event->status = 'realizado';
        $event->updated_by_user_uuid = $actorUserUuid;
        $event->updated_at = date('Y-m-d H:i:s');
        $event->save();

        $this->audit->log('schedule.events.done_marked', $actorUserUuid, ['event_uuid' => $event->uuid]);

        return $event;
    }
}
