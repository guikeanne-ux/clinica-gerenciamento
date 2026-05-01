<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\ValidationException;
use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEventType;

final class ResolveScheduleEventTypeService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function execute(?string $eventTypeUuid, bool $isAttendance, string $actorUserUuid): ScheduleEventType
    {
        $normalizedUuid = trim((string) ($eventTypeUuid ?? ''));

        if ($normalizedUuid !== '') {
            /** @var ScheduleEventType|null $eventType */
            $eventType = ScheduleEventType::query()
                ->where('uuid', $normalizedUuid)
                ->whereNull('deleted_at')
                ->first();

            if ($eventType === null) {
                throw new ValidationException('Erro de validação.', [[
                    'field' => 'event_type_uuid',
                    'message' => 'Tipo de evento não encontrado.',
                ]]);
            }

            return $eventType;
        }

        return $this->findOrCreateDefaultType($isAttendance, $actorUserUuid);
    }

    private function findOrCreateDefaultType(bool $isAttendance, string $actorUserUuid): ScheduleEventType
    {
        $defaultName = $isAttendance ? 'Atendimento' : 'Evento comum';
        $defaultCategory = $isAttendance ? 'atendimento' : 'evento_interno';

        /** @var ScheduleEventType|null $eventType */
        $eventType = ScheduleEventType::query()
            ->where('name', $defaultName)
            ->where('category', $defaultCategory)
            ->whereNull('deleted_at')
            ->first();

        if ($eventType !== null) {
            return $eventType;
        }

        $eventType = ScheduleEventType::query()->create([
            'uuid' => Uuid::v4(),
            'name' => $defaultName,
            'description' => $isAttendance
                ? 'Tipo padrão automático para atendimentos agendados.'
                : 'Tipo padrão automático para eventos administrativos.',
            'category' => $defaultCategory,
            'color' => $isAttendance ? '#2A7F62' : '#4C6678',
            'requires_patient' => $isAttendance,
            'requires_professional' => $isAttendance,
            'can_generate_attendance' => $isAttendance,
            'can_generate_financial_entry' => false,
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('schedule.event_types.auto_created', $actorUserUuid, [
            'event_type_uuid' => $eventType->uuid,
            'is_attendance' => $isAttendance,
        ]);

        return $eventType;
    }
}
