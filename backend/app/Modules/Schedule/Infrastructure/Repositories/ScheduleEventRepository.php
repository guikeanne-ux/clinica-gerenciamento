<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Infrastructure\Repositories;

use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;

final class ScheduleEventRepository
{
    public function findActiveByUuid(string $uuid): ?ScheduleEvent
    {
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();

        return $event;
    }

    public function paginateWithFilters(
        array $query,
        bool $canViewAll,
        ?string $viewerProfessionalUuid,
        string $viewerUserUuid
    ): array {
        $qb = DB::table('schedule_events as e')
            ->leftJoin('schedule_event_types as et', 'et.uuid', '=', 'e.event_type_uuid')
            ->leftJoin('professionals as p', 'p.uuid', '=', 'e.professional_uuid')
            ->leftJoin('patients as pa', 'pa.uuid', '=', 'e.patient_uuid')
            ->whereNull('e.deleted_at')
            ->whereNull('et.deleted_at')
            ->where(static function (Builder $q): void {
                $q->whereNull('p.deleted_at')->orWhereNull('e.professional_uuid');
            })
            ->where(static function (Builder $q): void {
                $q->whereNull('pa.deleted_at')->orWhereNull('e.patient_uuid');
            })
            ->select([
                'e.uuid',
                'e.title',
                'e.description',
                'e.event_type_uuid',
                'e.is_attendance',
                'e.patient_uuid',
                'e.professional_uuid',
                'e.starts_at',
                'e.ends_at',
                'e.all_day',
                'e.status',
                'e.origin',
                'e.recurrence_rule',
                'e.recurrence_group_uuid',
                'e.room_or_location',
                'e.color_override',
                'e.created_by_user_uuid',
                'e.updated_by_user_uuid',
                'e.canceled_at',
                'e.canceled_by_user_uuid',
                'e.cancel_reason',
                'e.created_at',
                'e.updated_at',
                'et.name as event_type_name',
                'et.category as event_type_category',
                'et.color as event_type_color',
                'p.schedule_color as professional_schedule_color',
                'p.full_name as professional_name',
                'pa.full_name as patient_name',
            ]);

        if (! $canViewAll) {
            $qb->where(static function (Builder $restricted) use ($viewerProfessionalUuid, $viewerUserUuid): void {
                $restricted->where('e.created_by_user_uuid', $viewerUserUuid);

                if ($viewerProfessionalUuid !== null && $viewerProfessionalUuid !== '') {
                    $restricted->orWhere('e.professional_uuid', $viewerProfessionalUuid);
                }
            });
        }

        $this->applyFilters($qb, $query);

        $sort = (string) ($query['sort'] ?? 'starts_at');
        $allowedSorts = [
            'starts_at',
            'ends_at',
            'status',
            'title',
            'created_at',
            'updated_at',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'starts_at';
        }

        $direction = strtolower((string) ($query['direction'] ?? 'asc'));
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $total = (clone $qb)->count();
        $items = $qb->orderBy('e.' . $sort, $direction)->forPage($page, $perPage)->get()->toArray();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    private function applyFilters(Builder $qb, array $query): void
    {
        $startDate = trim((string) ($query['start_date'] ?? ''));
        if ($startDate !== '') {
            $qb->where('e.ends_at', '>=', $startDate . ' 00:00:00');
        }

        $endDate = trim((string) ($query['end_date'] ?? ''));
        if ($endDate !== '') {
            $qb->where('e.starts_at', '<=', $endDate . ' 23:59:59');
        }

        foreach (['professional_uuid', 'patient_uuid', 'event_type_uuid', 'status'] as $filter) {
            $value = trim((string) ($query[$filter] ?? ''));
            if ($value !== '') {
                $qb->where('e.' . $filter, '=', $value);
            }
        }

        $category = trim((string) ($query['category'] ?? ''));
        if ($category !== '') {
            $qb->where('et.category', '=', $category);
        }

        $search = trim((string) ($query['search'] ?? ''));
        if ($search !== '') {
            $qb->where(static function (Builder $searchQb) use ($search): void {
                $searchQb->where('e.title', 'like', '%' . $search . '%')
                    ->orWhere('e.description', 'like', '%' . $search . '%')
                    ->orWhere('e.room_or_location', 'like', '%' . $search . '%');
            });
        }
    }
}
