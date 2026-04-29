<?php

declare(strict_types=1);

namespace App\Modules\Person\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Person\Application\PersonService;

final class PatientController
{
    public function __construct(private readonly PersonService $service = new PersonService())
    {
    }

    public function index(Request $request): array
    {
        return JsonResponse::make(ApiResponse::success(data: $this->service->listPatients($request->query)));
    }

    public function store(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $patient = $this->service->createPatient($request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Paciente criado com sucesso.', $patient->toArray()), 201);
    }

    public function show(Request $request): array
    {
        $patient = $this->service->getPatient((string) $request->attribute('uuid'));
        return JsonResponse::make(ApiResponse::success(data: $patient->toArray()));
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $patient = $this->service->updatePatient((string) $request->attribute('uuid'), $request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Paciente atualizado com sucesso.', $patient->toArray()));
    }

    public function delete(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $this->service->deletePatient((string) $request->attribute('uuid'), $actor);
        return JsonResponse::make(ApiResponse::success('Paciente removido com sucesso.'));
    }
}
