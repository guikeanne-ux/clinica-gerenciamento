<?php

declare(strict_types=1);

namespace App\Modules\Specialty\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Specialty\Application\SpecialtyService;

final class SpecialtyController
{
    public function __construct(private readonly SpecialtyService $service = new SpecialtyService())
    {
    }

    public function index(Request $request): array
    {
        return JsonResponse::make(ApiResponse::success(data: $this->service->list($request->query)));
    }

    public function store(Request $request): array
    {
        $specialty = $this->service->create($request->body);
        return JsonResponse::make(
            ApiResponse::success('Especialidade criada com sucesso.', $specialty->toArray()),
            201
        );
    }

    public function update(Request $request): array
    {
        $specialty = $this->service->update((string) $request->attribute('uuid'), $request->body);
        return JsonResponse::make(ApiResponse::success('Especialidade atualizada com sucesso.', $specialty->toArray()));
    }

    public function delete(Request $request): array
    {
        $this->service->delete((string) $request->attribute('uuid'));
        return JsonResponse::make(ApiResponse::success('Especialidade removida com sucesso.'));
    }
}
