<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Presentation;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;
use App\Modules\Schedule\Application\CancelScheduleEventService;
use App\Modules\Schedule\Application\ConfirmScheduleEventService;
use App\Modules\Schedule\Application\CreateScheduleEventService;
use App\Modules\Schedule\Application\DeleteScheduleEventService;
use App\Modules\Schedule\Application\GetScheduleEventService;
use App\Modules\Schedule\Application\ListScheduleEventsService;
use App\Modules\Schedule\Application\MarkScheduleAbsenceService;
use App\Modules\Schedule\Application\MarkScheduleEventDoneService;
use App\Modules\Schedule\Application\RescheduleScheduleEventService;
use App\Modules\Schedule\Application\UpdateScheduleEventService;

final class ScheduleEventController
{
    private readonly PermissionService $permissions;

    public function __construct(
        private readonly ListScheduleEventsService $listService = new ListScheduleEventsService(),
        private readonly CreateScheduleEventService $createService = new CreateScheduleEventService(),
        private readonly GetScheduleEventService $getService = new GetScheduleEventService(),
        private readonly UpdateScheduleEventService $updateService = new UpdateScheduleEventService(),
        private readonly DeleteScheduleEventService $deleteService = new DeleteScheduleEventService(),
        private readonly CancelScheduleEventService $cancelService = new CancelScheduleEventService(),
        private readonly MarkScheduleAbsenceService $markAbsenceService = new MarkScheduleAbsenceService(),
        private readonly ConfirmScheduleEventService $confirmService = new ConfirmScheduleEventService(),
        private readonly MarkScheduleEventDoneService $markDoneService = new MarkScheduleEventDoneService(),
        private readonly RescheduleScheduleEventService $rescheduleService = new RescheduleScheduleEventService()
    ) {
        $this->permissions = new PermissionService();
    }

    public function index(Request $request): array
    {
        $authUser = $request->attribute('auth_user');

        return JsonResponse::make(ApiResponse::success(data: $this->listService->execute($request->query, $authUser)));
    }

    public function store(Request $request): array
    {
        $event = $this->createService->execute($request->body, $request->attribute('auth_user'));

        if (is_array($event)) {
            return JsonResponse::make(ApiResponse::success('Eventos recorrentes criados com sucesso.', $event), 201);
        }

        return JsonResponse::make(ApiResponse::success('Evento criado com sucesso.', $event->toArray()), 201);
    }

    public function show(Request $request): array
    {
        $event = $this->getService->execute((string) $request->attribute('uuid'), $request->attribute('auth_user'));

        return JsonResponse::make(ApiResponse::success(data: $event->toArray()));
    }

    public function update(Request $request): array
    {
        $this->assertCanMutateEvent(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        $event = $this->updateService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );

        return JsonResponse::make(ApiResponse::success('Evento atualizado com sucesso.', $event->toArray()));
    }

    public function delete(Request $request): array
    {
        $this->assertCanMutateEvent(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        $actor = (string) $request->attribute('auth_user')->uuid;
        $this->deleteService->execute((string) $request->attribute('uuid'), $actor);

        return JsonResponse::make(ApiResponse::success('Evento removido com sucesso.'));
    }

    public function cancel(Request $request): array
    {
        $this->assertCanMutateEvent(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        $actor = (string) $request->attribute('auth_user')->uuid;
        $event = $this->cancelService->execute((string) $request->attribute('uuid'), $request->body, $actor);

        return JsonResponse::make(ApiResponse::success('Evento cancelado com sucesso.', $event->toArray()));
    }

    public function markAbsence(Request $request): array
    {
        $this->assertCanMutateEvent(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        $actor = (string) $request->attribute('auth_user')->uuid;
        $event = $this->markAbsenceService->execute((string) $request->attribute('uuid'), $actor);

        return JsonResponse::make(ApiResponse::success('Falta registrada com sucesso.', $event->toArray()));
    }

    public function confirm(Request $request): array
    {
        $this->assertCanMutateEvent(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        $actor = (string) $request->attribute('auth_user')->uuid;
        $event = $this->confirmService->execute((string) $request->attribute('uuid'), $actor);

        return JsonResponse::make(ApiResponse::success('Evento confirmado com sucesso.', $event->toArray()));
    }

    public function markDone(Request $request): array
    {
        $this->assertCanMutateEvent(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        $actor = (string) $request->attribute('auth_user')->uuid;
        $event = $this->markDoneService->execute((string) $request->attribute('uuid'), $actor);

        return JsonResponse::make(ApiResponse::success('Evento marcado como realizado.', $event->toArray()));
    }

    public function reschedule(Request $request): array
    {
        $this->assertCanMutateEvent(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        $event = $this->rescheduleService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );

        return JsonResponse::make(ApiResponse::success('Evento remarcado com sucesso.', $event->toArray()));
    }

    private function assertCanMutateEvent(string $eventUuid, User $authUser): void
    {
        if ($this->permissions->has($authUser, 'schedule.view_all')) {
            return;
        }

        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $eventUuid)->whereNull('deleted_at')->first();
        if ($event === null) {
            return;
        }

        $sameCreator = (string) $event->created_by_user_uuid === (string) $authUser->uuid;
        $sameProfessional = $authUser->professional_uuid !== null
            && (string) $authUser->professional_uuid !== ''
            && (string) $event->professional_uuid === (string) $authUser->professional_uuid;

        if ($sameCreator || $sameProfessional) {
            return;
        }

        throw new AuthorizationException('Você pode alterar apenas compromissos próprios.');
    }
}
