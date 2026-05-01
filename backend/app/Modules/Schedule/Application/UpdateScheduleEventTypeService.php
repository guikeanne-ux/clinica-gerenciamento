<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEventType;

final class UpdateScheduleEventTypeService
{
    public function __construct(
        private readonly GetScheduleEventTypeService $getService = new GetScheduleEventTypeService(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $uuid, array $data, string $actorUserUuid): ScheduleEventType
    {
        $eventType = $this->getService->execute($uuid);
        $merged = array_merge($eventType->toArray(), $data);

        $name = ScheduleValidation::assertRequiredString($merged, 'name', 'Nome do tipo de evento é obrigatório.');
        $category = ScheduleValidation::assertRequiredString(
            $merged,
            'category',
            'Categoria do tipo de evento é obrigatória.'
        );
        ScheduleValidation::assertIn(
            $category,
            ScheduleConstants::EVENT_TYPE_CATEGORIES,
            'category',
            'Categoria de tipo de evento inválida.'
        );

        $status = trim((string) ($merged['status'] ?? 'ativo'));
        ScheduleValidation::assertIn(
            $status,
            ScheduleConstants::EVENT_TYPE_STATUSES,
            'status',
            'Status do tipo de evento inválido.'
        );

        $color = isset($merged['color']) ? trim((string) $merged['color']) : null;
        $color = $color === '' ? null : $color;
        ScheduleValidation::assertHexColor($color, 'color');

        $eventType->name = $name;
        $eventType->description = $this->nullableText($merged['description'] ?? null);
        $eventType->category = $category;
        $eventType->color = $color;
        $eventType->requires_patient = ScheduleValidation::normalizeBool(
            $merged['requires_patient'] ?? false
        );
        $eventType->requires_professional = ScheduleValidation::normalizeBool(
            $merged['requires_professional'] ?? false
        );
        $eventType->can_generate_attendance = ScheduleValidation::normalizeBool(
            $merged['can_generate_attendance'] ?? false
        );
        $eventType->can_generate_financial_entry = ScheduleValidation::normalizeBool(
            $merged['can_generate_financial_entry'] ?? false
        );
        $eventType->status = $status;
        $eventType->updated_at = date('Y-m-d H:i:s');
        $eventType->save();

        $this->audit->log('schedule.event_types.updated', $actorUserUuid, ['event_type_uuid' => $eventType->uuid]);

        return $eventType;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
