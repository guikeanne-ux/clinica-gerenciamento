<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

final class ResolveScheduleEventColorService
{
    public function execute(array $eventRow): array
    {
        $overrideColor = $this->normalizeColor($eventRow['color_override'] ?? null);
        if ($overrideColor !== null) {
            return [
                'resolved_color' => $overrideColor,
                'resolved_color_source' => 'event_override',
            ];
        }

        $professionalColor = $this->normalizeColor($eventRow['professional_schedule_color'] ?? null);
        if ($professionalColor !== null) {
            return [
                'resolved_color' => $professionalColor,
                'resolved_color_source' => 'professional',
            ];
        }

        $typeColor = $this->normalizeColor($eventRow['event_type_color'] ?? null);
        if ($typeColor !== null) {
            return [
                'resolved_color' => $typeColor,
                'resolved_color_source' => 'event_type',
            ];
        }

        return [
            'resolved_color' => '#157470',
            'resolved_color_source' => 'default',
        ];
    }

    private function normalizeColor(mixed $value): ?string
    {
        $text = strtoupper(trim((string) ($value ?? '')));
        if (! preg_match('/^#[0-9A-F]{6}$/', $text)) {
            return null;
        }

        return $text;
    }
}
