<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class CancelScheduleEventService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function execute(string $eventUuid, array $data, string $actorUserUuid): ScheduleEvent
    {
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $eventUuid)->whereNull('deleted_at')->first();
        if ($event === null) {
            throw new NotFoundException('Evento da agenda não encontrado.');
        }

        if ($event->status === 'cancelado') {
            return $event;
        }

        $cancelReason = trim((string) ($data['cancel_reason'] ?? ''));
        if ($cancelReason === '') {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'cancel_reason',
                'message' => 'Motivo do cancelamento é obrigatório.',
            ]]);
        }

        $event->status = 'cancelado';
        $event->canceled_at = date('Y-m-d H:i:s');
        $event->canceled_by_user_uuid = $actorUserUuid;
        $event->cancel_reason = $cancelReason;
        $event->updated_by_user_uuid = $actorUserUuid;
        $event->updated_at = date('Y-m-d H:i:s');
        $event->save();

        $this->audit->log('schedule.events.canceled', $actorUserUuid, [
            'event_uuid' => $event->uuid,
            'cancel_reason' => $cancelReason,
        ]);

        return $event;
    }
}
