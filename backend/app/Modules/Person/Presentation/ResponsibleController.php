<?php

declare(strict_types=1);

namespace App\Modules\Person\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Person\Application\PersonService;

final class ResponsibleController
{
    public function __construct(private readonly PersonService $service = new PersonService())
    {
    }

    public function index(Request $request): array
    {
        $patientUuid = (string) $request->attribute('uuid');
        $responsibles = $this->service->listResponsibles($patientUuid);

        return JsonResponse::make(ApiResponse::success(data: $responsibles));
    }

    public function store(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $resp = $this->service->createResponsible((string) $request->attribute('uuid'), $request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Responsável criado com sucesso.', $resp->toArray()), 201);
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $resp = $this->service->updateResponsible((string) $request->attribute('uuid'), $request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Responsável atualizado com sucesso.', $resp->toArray()));
    }

    public function delete(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $this->service->deleteResponsible((string) $request->attribute('uuid'), $actor);
        return JsonResponse::make(ApiResponse::success('Responsável removido com sucesso.'));
    }
}
