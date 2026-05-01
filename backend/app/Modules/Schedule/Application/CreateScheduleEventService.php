<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\ValidationException;
use App\Core\Support\Uuid;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class CreateScheduleEventService
{
    private readonly CreateRecurringScheduleEventsService $createRecurringService;
    private readonly ResolveScheduleEventTypeService $eventTypeResolver;

    public function __construct(
        private readonly CheckScheduleConflictService $conflictService = new CheckScheduleConflictService(),
        private readonly PermissionService $permissions = new PermissionService(),
        private readonly AuditService $audit = new AuditService(),
        ?CreateRecurringScheduleEventsService $createRecurringService = null,
        ?ResolveScheduleEventTypeService $eventTypeResolver = null
    ) {
        $this->createRecurringService = $createRecurringService ?? new CreateRecurringScheduleEventsService();
        $this->eventTypeResolver = $eventTypeResolver ?? new ResolveScheduleEventTypeService();
    }

    public function execute(array $data, User $authUser): ScheduleEvent|array
    {
        $actorUserUuid = (string) $authUser->uuid;
        $title = ScheduleValidation::assertRequiredString($data, 'title', 'Título do evento é obrigatório.');
        $startsAt = ScheduleValidation::assertRequiredString($data, 'starts_at', 'Início do evento é obrigatório.');
        $endsAt = ScheduleValidation::assertRequiredString($data, 'ends_at', 'Fim do evento é obrigatório.');

        ScheduleValidation::assertDateRange($startsAt, $endsAt);

        $status = trim((string) ($data['status'] ?? 'agendado'));
        ScheduleValidation::assertIn(
            $status,
            ScheduleConstants::EVENT_STATUSES,
            'status',
            'Status de evento inválido.'
        );

        $origin = trim((string) ($data['origin'] ?? 'manual'));
        ScheduleValidation::assertIn($origin, ScheduleConstants::EVENT_ORIGINS, 'origin', 'Origem do evento inválida.');

        $isAttendance = ScheduleValidation::normalizeBool($data['is_attendance'] ?? false);
        $eventType = $this->eventTypeResolver->execute(
            $this->nullableString($data['event_type_uuid'] ?? null),
            $isAttendance,
            $actorUserUuid
        );
        $eventTypeUuid = (string) $eventType->uuid;
        $patientUuid = $this->nullableString($data['patient_uuid'] ?? null);
        $professionalUuid = $this->nullableString($data['professional_uuid'] ?? null);
        if (! array_key_exists('is_attendance', $data)) {
            $isAttendance = (bool) $eventType->can_generate_attendance
                || ($patientUuid !== null && $professionalUuid !== null);
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

        $colorOverride = $this->nullableString($data['color_override'] ?? null);
        ScheduleValidation::assertHexColor($colorOverride, 'color_override');
        if ($colorOverride !== null && ! $this->permissions->has($authUser, 'schedule.override_conflict')) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'color_override',
                'message' => 'Você não possui permissão para definir cor manual do compromisso.',
            ]]);
        }

        $normalizedStartsAt = date('Y-m-d H:i:s', (int) strtotime($startsAt));
        $normalizedEndsAt = date('Y-m-d H:i:s', (int) strtotime($endsAt));
        $allowOverrideConflict = $this->permissions->has($authUser, 'schedule.override_conflict');

        if ($status !== 'cancelado') {
            $conflicts = $this->conflictService->assertNoConflict(
                $professionalUuid,
                $normalizedStartsAt,
                $normalizedEndsAt,
                null,
                $allowOverrideConflict
            );

            if ($allowOverrideConflict && $conflicts !== []) {
                $this->audit->log('schedule.events.conflict_overridden', $actorUserUuid, [
                    'action' => 'create',
                    'professional_uuid' => $professionalUuid,
                    'starts_at' => $normalizedStartsAt,
                    'ends_at' => $normalizedEndsAt,
                    'conflicts' => $conflicts,
                ]);
            }
        }

        if (isset($data['recurrence']) && is_array($data['recurrence'])) {
            return $this->createRecurringService->execute([
                'title' => $title,
                'description' => $this->nullableString($data['description'] ?? null),
                'event_type_uuid' => $eventTypeUuid,
                'is_attendance' => $isAttendance,
                'patient_uuid' => $patientUuid,
                'professional_uuid' => $professionalUuid,
                'starts_at' => $normalizedStartsAt,
                'ends_at' => $normalizedEndsAt,
                'all_day' => ScheduleValidation::normalizeBool($data['all_day'] ?? false),
                'status' => $status,
                'origin' => $origin,
                'room_or_location' => $this->nullableString($data['room_or_location'] ?? null),
                'color_override' => $colorOverride,
                'recurrence' => $data['recurrence'],
            ], $data['recurrence'], $actorUserUuid, $allowOverrideConflict);
        }

        $event = ScheduleEvent::query()->create([
            'uuid' => Uuid::v4(),
            'title' => $title,
            'description' => $this->nullableString($data['description'] ?? null),
            'event_type_uuid' => $eventTypeUuid,
            'is_attendance' => $isAttendance,
            'patient_uuid' => $patientUuid,
            'professional_uuid' => $professionalUuid,
            'starts_at' => $normalizedStartsAt,
            'ends_at' => $normalizedEndsAt,
            'all_day' => ScheduleValidation::normalizeBool($data['all_day'] ?? false),
            'status' => $status,
            'origin' => $origin,
            'recurrence_rule' => $this->normalizeRecurrenceRule($data),
            'recurrence_group_uuid' => $this->nullableString($data['recurrence_group_uuid'] ?? null),
            'room_or_location' => $this->nullableString($data['room_or_location'] ?? null),
            'color_override' => $colorOverride,
            'created_by_user_uuid' => $actorUserUuid,
            'updated_by_user_uuid' => $actorUserUuid,
            'canceled_at' => $status === 'cancelado' ? date('Y-m-d H:i:s') : null,
            'canceled_by_user_uuid' => $status === 'cancelado' ? $actorUserUuid : null,
            'cancel_reason' => $status === 'cancelado' ? $this->nullableString($data['cancel_reason'] ?? null) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('schedule.events.created', $actorUserUuid, ['event_uuid' => $event->uuid]);

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

    private function normalizeRecurrenceRule(array $data): ?string
    {
        if (isset($data['recurrence']) && is_array($data['recurrence'])) {
            return json_encode($data['recurrence'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->nullableString($data['recurrence_rule'] ?? null);
    }
}
