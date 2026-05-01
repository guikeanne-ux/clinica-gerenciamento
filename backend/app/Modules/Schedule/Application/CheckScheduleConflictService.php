<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\ErrorCode;
use Illuminate\Database\Capsule\Manager as DB;

final class CheckScheduleConflictService
{
    public function assertNoConflict(
        ?string $professionalUuid,
        string $startsAt,
        string $endsAt,
        ?string $excludeEventUuid = null,
        bool $allowOverride = false
    ): array {
        if ($professionalUuid === null || trim($professionalUuid) === '') {
            return [];
        }

        $conflicts = $this->findConflicts($professionalUuid, $startsAt, $endsAt, $excludeEventUuid);
        if ($conflicts === [] || $allowOverride) {
            return $conflicts;
        }

        throw new ConflictException(
            'Este profissional já possui compromisso nesse horário.',
            ErrorCode::SCHEDULE_CONFLICT,
            ['conflicts' => $conflicts]
        );
    }

    public function findConflicts(
        string $professionalUuid,
        string $startsAt,
        string $endsAt,
        ?string $excludeEventUuid = null
    ): array {
        $query = DB::table('schedule_events as e')
            ->leftJoin('schedule_event_types as et', 'et.uuid', '=', 'e.event_type_uuid')
            ->where('e.professional_uuid', $professionalUuid)
            ->whereNull('e.deleted_at')
            ->where('e.status', '!=', 'cancelado')
            ->where('e.starts_at', '<', $endsAt)
            ->where('e.ends_at', '>', $startsAt)
            ->select([
                'e.uuid',
                'e.title',
                'e.starts_at',
                'e.ends_at',
                'e.status',
                'et.category as event_type_category',
            ]);

        if ($excludeEventUuid !== null) {
            $query->where('e.uuid', '!=', $excludeEventUuid);
        }

        return $query->get()->map(static fn ($row): array => [
            'uuid' => (string) $row->uuid,
            'title' => (string) $row->title,
            'starts_at' => (string) $row->starts_at,
            'ends_at' => (string) $row->ends_at,
            'status' => (string) $row->status,
            'category' => (string) ($row->event_type_category ?? ''),
        ])->all();
    }
}
