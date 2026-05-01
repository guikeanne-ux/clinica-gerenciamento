<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class UpdateScheduleEventService
{
    private readonly ResolveScheduleEventTypeService $eventTypeResolver;

    public function __construct(
        private readonly CheckScheduleConflictService $conflictService = new CheckScheduleConflictService(),
        private readonly PermissionService $permissions = new PermissionService(),
        private readonly AuditService $audit = new AuditService(),
        ?ResolveScheduleEventTypeService $eventTypeResolver = null
    ) {
        $this->eventTypeResolver = $eventTypeResolver ?? new ResolveScheduleEventTypeService();
    }

    public function execute(string $uuid, array $data, User $authUser): ScheduleEvent
    {
        $actorUserUuid = (string) $authUser->uuid;
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();

        if ($event === null) {
            throw new NotFoundException('Evento da agenda não encontrado.');
        }

        $merged = array_merge($event->toArray(), $data);

        $title = ScheduleValidation::assertRequiredString($merged, 'title', 'Título do evento é obrigatório.');
        $startsAt = ScheduleValidation::assertRequiredString($merged, 'starts_at', 'Início do evento é obrigatório.');
        $endsAt = ScheduleValidation::assertRequiredString($merged, 'ends_at', 'Fim do evento é obrigatório.');

        ScheduleValidation::assertDateRange($startsAt, $endsAt);

        $status = trim((string) ($merged['status'] ?? 'agendado'));
        ScheduleValidation::assertIn(
            $status,
            ScheduleConstants::EVENT_STATUSES,
            'status',
            'Status de evento inválido.'
        );

        $origin = trim((string) ($merged['origin'] ?? 'manual'));
        ScheduleValidation::assertIn($origin, ScheduleConstants::EVENT_ORIGINS, 'origin', 'Origem do evento inválida.');

        $isAttendance = array_key_exists('is_attendance', $data)
            ? ScheduleValidation::normalizeBool($data['is_attendance'])
            : (bool) $event->is_attendance;
        $eventType = $this->eventTypeResolver->execute(
            $this->nullableString($merged['event_type_uuid'] ?? null),
            $isAttendance,
            $actorUserUuid
        );
        $eventTypeUuid = (string) $eventType->uuid;
        $patientUuid = $this->nullableString($merged['patient_uuid'] ?? null);
        $professionalUuid = $this->nullableString($merged['professional_uuid'] ?? null);
        if (! array_key_exists('is_attendance', $data)) {
            $isAttendance = (bool) $eventType->can_generate_attendance
                || ($patientUuid !== null && $professionalUuid !== null)
                || (bool) $event->is_attendance;
        }

        if ($isAttendance && $patientUuid === null) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'patient_uuid',
                'message' => 'Paciente é obrigatório para atendimento agendado.',
            ]]);
        }

        if ($isAttendance && $professionalUuid === null) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'professional_uuid',
                'message' => 'Profissional é obrigatório para atendimento agendado.',
            ]]);
        }

        if ((bool) $eventType->requires_patient && $patientUuid === null) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'patient_uuid',
                'message' => 'Paciente é obrigatório para este tipo de evento.',
            ]]);
        }

        if ((bool) $eventType->requires_professional && $professionalUuid === null) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'professional_uuid',
                'message' => 'Profissional é obrigatório para este tipo de evento.',
            ]]);
        }

        if ($patientUuid !== null) {
            ScheduleValidation::assertUuidExists('patients', $patientUuid, 'patient_uuid', 'Paciente não encontrado.');
        }

        if ($professionalUuid !== null) {
            ScheduleValidation::assertUuidExists(
                'professionals',
                $professionalUuid,
                'professional_uuid',
                'Profissional não encontrado.'
            );
        }

        $colorOverride = $this->nullableString($merged['color_override'] ?? null);
        ScheduleValidation::assertHexColor($colorOverride, 'color_override');
        if ($colorOverride !== null && ! $this->permissions->has($authUser, 'schedule.override_conflict')) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'color_override',
                'message' => 'Você não possui permissão para definir cor manual do compromisso.',
            ]]);
        }

        $normalizedStartsAt = date('Y-m-d H:i:s', (int) strtotime($startsAt));
        $normalizedEndsAt = date('Y-m-d H:i:s', (int) strtotime($endsAt));

        if ($status !== 'cancelado') {
            $allowOverrideConflict = $this->permissions->has($authUser, 'schedule.override_conflict');
            $conflicts = $this->conflictService->assertNoConflict(
                $professionalUuid,
                $normalizedStartsAt,
                $normalizedEndsAt,
                $event->uuid,
                $allowOverrideConflict
            );

            if ($allowOverrideConflict && $conflicts !== []) {
                $this->audit->log('schedule.events.conflict_overridden', $actorUserUuid, [
                    'action' => 'update',
                    'event_uuid' => $event->uuid,
                    'professional_uuid' => $professionalUuid,
                    'starts_at' => $normalizedStartsAt,
                    'ends_at' => $normalizedEndsAt,
                    'conflicts' => $conflicts,
                ]);
            }
        }

        $event->title = $title;
        $event->description = $this->nullableString($merged['description'] ?? null);
        $event->event_type_uuid = $eventTypeUuid;
        $event->is_attendance = $isAttendance;
        $event->patient_uuid = $patientUuid;
        $event->professional_uuid = $professionalUuid;
        $event->starts_at = $normalizedStartsAt;
        $event->ends_at = $normalizedEndsAt;
        $event->all_day = ScheduleValidation::normalizeBool($merged['all_day'] ?? false);
        $event->status = $status;
        $event->origin = $origin;
        $event->recurrence_rule = $this->nullableString($merged['recurrence_rule'] ?? null);
        $event->recurrence_group_uuid = $this->nullableString($merged['recurrence_group_uuid'] ?? null);
        $event->room_or_location = $this->nullableString($merged['room_or_location'] ?? null);
        $event->color_override = $colorOverride;
        $event->updated_by_user_uuid = $actorUserUuid;

        if ($status === 'cancelado') {
            if ($event->canceled_at === null) {
                $event->canceled_at = date('Y-m-d H:i:s');
            }

            $event->canceled_by_user_uuid = $actorUserUuid;
            $event->cancel_reason = $this->nullableString($merged['cancel_reason'] ?? null);
        } else {
            $event->canceled_at = null;
            $event->canceled_by_user_uuid = null;
            $event->cancel_reason = null;
        }

        $event->updated_at = date('Y-m-d H:i:s');
        $event->save();

        $this->audit->log('schedule.events.updated', $actorUserUuid, ['event_uuid' => $event->uuid]);

        return $event;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
