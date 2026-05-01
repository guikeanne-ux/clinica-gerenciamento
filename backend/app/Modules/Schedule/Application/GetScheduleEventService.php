<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\NotFoundException;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class GetScheduleEventService
{
    public function __construct(private readonly PermissionService $permissions = new PermissionService())
    {
    }

    public function execute(string $uuid, User $authUser): ScheduleEvent
    {
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();

        if ($event === null) {
            throw new NotFoundException('Evento da agenda não encontrado.');
        }

        if (! $this->permissions->has($authUser, 'schedule.view_all')) {
            $professionalUuid = (string) ($authUser->professional_uuid ?? '');
            $canViewOwn = $event->created_by_user_uuid === $authUser->uuid;
            $canViewProfessional = $professionalUuid !== '' && $event->professional_uuid === $professionalUuid;

            if (! $canViewOwn && ! $canViewProfessional) {
                throw new NotFoundException('Evento da agenda não encontrado.');
            }
        }

        return $event;
    }
}
