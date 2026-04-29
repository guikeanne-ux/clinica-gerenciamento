<?php

declare(strict_types=1);

namespace App\Modules\Person\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Person\Application\PersonService;

final class ProfessionalController
{
    public function __construct(private readonly PersonService $service = new PersonService())
    {
    }

    public function index(Request $request): array
    {
        $professionals = $this->service->listProfessionals($request->query);

        return JsonResponse::make(ApiResponse::success(data: $professionals));
    }

    public function store(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $professional = $this->service->createProfessional($request->body, $actor);
        return JsonResponse::make(
            ApiResponse::success('Profissional criado com sucesso.', $professional->toArray()),
            201
        );
    }

    public function show(Request $request): array
    {
        $professional = $this->service->getProfessional((string) $request->attribute('uuid'));
        return JsonResponse::make(ApiResponse::success(data: $professional->toArray()));
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $professional = $this->service->updateProfessional(
            (string) $request->attribute('uuid'),
            $request->body,
            $actor
        );

        return JsonResponse::make(
            ApiResponse::success('Profissional atualizado com sucesso.', $professional->toArray())
        );
    }

    public function delete(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $this->service->deleteProfessional((string) $request->attribute('uuid'), $actor);
        return JsonResponse::make(ApiResponse::success('Profissional removido com sucesso.'));
    }

    public function createUser(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $professional = $this->service->createUserForProfessional(
            (string) $request->attribute('uuid'),
            $actor
        );

        return JsonResponse::make(
            ApiResponse::success('Usuário criado e vinculado ao profissional.', $professional->toArray())
        );
    }
}
