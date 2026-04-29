<?php

declare(strict_types=1);

namespace App\Modules\Person\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Person\Application\PersonService;

final class SupplierController
{
    public function __construct(private readonly PersonService $service = new PersonService())
    {
    }

    public function index(Request $request): array
    {
        return JsonResponse::make(ApiResponse::success(data: $this->service->listSuppliers($request->query)));
    }

    public function store(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $supplier = $this->service->createSupplier($request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Fornecedor criado com sucesso.', $supplier->toArray()), 201);
    }

    public function show(Request $request): array
    {
        $supplier = $this->service->getSupplier((string) $request->attribute('uuid'));
        return JsonResponse::make(ApiResponse::success(data: $supplier->toArray()));
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $supplier = $this->service->updateSupplier((string) $request->attribute('uuid'), $request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Fornecedor atualizado com sucesso.', $supplier->toArray()));
    }

    public function delete(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $this->service->deleteSupplier((string) $request->attribute('uuid'), $actor);
        return JsonResponse::make(ApiResponse::success('Fornecedor removido com sucesso.'));
    }
}
