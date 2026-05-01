<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\NotFoundException;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class RescheduleScheduleEventService
{
    public function __construct(
        private readonly CheckScheduleConflictService $conflictService = new CheckScheduleConflictService(),
        private readonly PermissionService $permissions = new PermissionService(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $eventUuid, array $data, User $authUser): ScheduleEvent
    {
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $eventUuid)->whereNull('deleted_at')->first();
        if ($event === null) {
            throw new NotFoundException('Evento da agenda não encontrado.');
        }

        if ($event->status === 'cancelado') {
            throw new BusinessRuleException('Evento cancelado não pode ser remarcado.');
        }

        $startsAt = ScheduleValidation::assertRequiredString($data, 'starts_at', 'Início do evento é obrigatório.');
        $endsAt = ScheduleValidation::assertRequiredString($data, 'ends_at', 'Fim do evento é obrigatório.');
        ScheduleValidation::assertDateRange($startsAt, $endsAt);

        $newStartsAt = date('Y-m-d H:i:s', (int) strtotime($startsAt));
        $newEndsAt = date('Y-m-d H:i:s', (int) strtotime($endsAt));

        $allowOverrideConflict = $this->permissions->has($authUser, 'schedule.override_conflict');

        $professionalUuid = $event->professional_uuid;
        $conflicts = $this->conflictService->assertNoConflict(
            $professionalUuid,
            $newStartsAt,
            $newEndsAt,
            $event->uuid,
            $allowOverrideConflict
        );

        if ($allowOverrideConflict && $conflicts !== []) {
            $this->audit->log('schedule.events.conflict_overridden', (string) $authUser->uuid, [
                'action' => 'reschedule',
                'event_uuid' => $event->uuid,
                'professional_uuid' => $professionalUuid,
                'starts_at' => $newStartsAt,
                'ends_at' => $newEndsAt,
                'conflicts' => $conflicts,
            ]);
        }

        $before = [
            'starts_at' => (string) $event->starts_at,
            'ends_at' => (string) $event->ends_at,
            'status' => (string) $event->status,
        ];

        $event->starts_at = $newStartsAt;
        $event->ends_at = $newEndsAt;
        $event->status = 'remarcado';
        $event->updated_by_user_uuid = (string) $authUser->uuid;
        $event->updated_at = date('Y-m-d H:i:s');
        $event->save();

        $this->audit->log('schedule.events.rescheduled', (string) $authUser->uuid, [
            'event_uuid' => $event->uuid,
            'before' => $before,
            'after' => [
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'status' => $event->status,
            ],
            'reason' => trim((string) ($data['reason'] ?? '')),
        ]);

        return $event;
    }
}
