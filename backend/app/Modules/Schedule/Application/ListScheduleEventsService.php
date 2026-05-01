<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Modules\ACL\Application\PermissionService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Schedule\Infrastructure\Repositories\ScheduleEventRepository;

final class ListScheduleEventsService
{
    public function __construct(
        private readonly ScheduleEventRepository $repository = new ScheduleEventRepository(),
        private readonly PermissionService $permissions = new PermissionService(),
        private readonly ResolveScheduleEventColorService $colorResolver = new ResolveScheduleEventColorService()
    ) {
    }

    public function execute(array $query, User $authUser): array
    {
        $canViewAll = $this->permissions->has($authUser, 'schedule.view_all')
            || $this->permissions->has($authUser, 'schedule.view');
        $professionalUuid = $authUser->professional_uuid !== null ? (string) $authUser->professional_uuid : null;

        $result = $this->repository->paginateWithFilters(
            $query,
            $canViewAll,
            $professionalUuid,
            (string) $authUser->uuid
        );

        $items = array_map(function (object $row): array {
            $item = (array) $row;
            return array_merge($item, $this->colorResolver->execute($item));
        }, $result['items']);

        $result['items'] = $items;

        return $result;
    }
}
