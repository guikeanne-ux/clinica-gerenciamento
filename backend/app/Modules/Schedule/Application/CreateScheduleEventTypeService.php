<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEventType;

final class CreateScheduleEventTypeService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function execute(array $data, string $actorUserUuid): ScheduleEventType
    {
        $name = ScheduleValidation::assertRequiredString($data, 'name', 'Nome do tipo de evento é obrigatório.');
        $category = ScheduleValidation::assertRequiredString(
            $data,
            'category',
            'Categoria do tipo de evento é obrigatória.'
        );
        ScheduleValidation::assertIn(
            $category,
            ScheduleConstants::EVENT_TYPE_CATEGORIES,
            'category',
            'Categoria de tipo de evento inválida.'
        );

        $status = trim((string) ($data['status'] ?? 'ativo'));
        ScheduleValidation::assertIn(
            $status,
            ScheduleConstants::EVENT_TYPE_STATUSES,
            'status',
            'Status do tipo de evento inválido.'
        );

        $color = isset($data['color']) ? trim((string) $data['color']) : null;
        $color = $color === '' ? null : $color;
        ScheduleValidation::assertHexColor($color, 'color');

        $eventType = ScheduleEventType::query()->create([
            'uuid' => Uuid::v4(),
            'name' => $name,
            'description' => $this->nullableText($data['description'] ?? null),
            'category' => $category,
            'color' => $color,
            'requires_patient' => ScheduleValidation::normalizeBool($data['requires_patient'] ?? false),
            'requires_professional' => ScheduleValidation::normalizeBool($data['requires_professional'] ?? false),
            'can_generate_attendance' => ScheduleValidation::normalizeBool($data['can_generate_attendance'] ?? false),
            'can_generate_financial_entry' => ScheduleValidation::normalizeBool(
                $data['can_generate_financial_entry'] ?? false
            ),
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('schedule.event_types.created', $actorUserUuid, ['event_type_uuid' => $eventType->uuid]);

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
