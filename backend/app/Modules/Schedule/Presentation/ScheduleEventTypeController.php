<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Schedule\Application\CreateScheduleEventTypeService;
use App\Modules\Schedule\Application\DeleteScheduleEventTypeService;
use App\Modules\Schedule\Application\GetScheduleEventTypeService;
use App\Modules\Schedule\Application\ListScheduleEventTypesService;
use App\Modules\Schedule\Application\UpdateScheduleEventTypeService;

final class ScheduleEventTypeController
{
    public function __construct(
        private readonly ListScheduleEventTypesService $listService = new ListScheduleEventTypesService(),
        private readonly CreateScheduleEventTypeService $createService = new CreateScheduleEventTypeService(),
        private readonly GetScheduleEventTypeService $getService = new GetScheduleEventTypeService(),
        private readonly UpdateScheduleEventTypeService $updateService = new UpdateScheduleEventTypeService(),
        private readonly DeleteScheduleEventTypeService $deleteService = new DeleteScheduleEventTypeService()
    ) {
    }

    public function index(Request $request): array
    {
        return JsonResponse::make(ApiResponse::success(data: $this->listService->execute($request->query)));
    }

    public function store(Request $request): array
    {
        $actor = (string) $request->attribute('auth_user')->uuid;
        $eventType = $this->createService->execute($request->body, $actor);

        return JsonResponse::make(
            ApiResponse::success('Tipo de evento criado com sucesso.', $eventType->toArray()),
            201
        );
    }

    public function show(Request $request): array
    {
        $eventType = $this->getService->execute((string) $request->attribute('uuid'));

        return JsonResponse::make(ApiResponse::success(data: $eventType->toArray()));
    }

    public function update(Request $request): array
    {
        $actor = (string) $request->attribute('auth_user')->uuid;
        $eventType = $this->updateService->execute((string) $request->attribute('uuid'), $request->body, $actor);

        return JsonResponse::make(
            ApiResponse::success('Tipo de evento atualizado com sucesso.', $eventType->toArray())
        );
    }

    public function delete(Request $request): array
    {
        $actor = (string) $request->attribute('auth_user')->uuid;
        $this->deleteService->execute((string) $request->attribute('uuid'), $actor);

        return JsonResponse::make(ApiResponse::success('Tipo de evento removido com sucesso.'));
    }
}
