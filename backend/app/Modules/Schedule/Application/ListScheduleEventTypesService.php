<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Modules\Schedule\Infrastructure\Models\ScheduleEventType;

final class ListScheduleEventTypesService
{
    public function execute(array $query): array
    {
        $qb = ScheduleEventType::query()->whereNull('deleted_at');

        $status = trim((string) ($query['status'] ?? ''));
        if ($status !== '') {
            $qb->where('status', $status);
        }

        $category = trim((string) ($query['category'] ?? ''));
        if ($category !== '') {
            $qb->where('category', $category);
        }

        $search = trim((string) ($query['search'] ?? ''));
        if ($search !== '') {
            $qb->where(static function ($searchQb) use ($search): void {
                $searchQb->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        return $qb->orderBy('name', 'asc')->get()->toArray();
    }
}
