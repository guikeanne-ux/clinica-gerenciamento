<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\ProfessionalPayment\Application\CreatePaymentTableService;
use App\Modules\ProfessionalPayment\Application\ProfessionalPaymentService;
use App\Modules\ProfessionalPayment\Application\UpdatePaymentTableService;

final class PaymentTableController
{
    public function __construct(
        private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService(),
        private readonly CreatePaymentTableService $createService = new CreatePaymentTableService(),
        private readonly UpdatePaymentTableService $updateService = new UpdatePaymentTableService()
    ) {
    }

    public function index(Request $request): array
    {
        return JsonResponse::make(ApiResponse::success(data: $this->service->listPaymentTables($request->query)));
    }

    public function store(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $table = $this->createService->execute($request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Tabela criada com sucesso.', $table->toArray()), 201);
    }

    public function show(Request $request): array
    {
        $table = $this->service->getPaymentTable((string) $request->attribute('uuid'));
        return JsonResponse::make(ApiResponse::success(data: $table->toArray()));
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $table = $this->updateService->execute((string) $request->attribute('uuid'), $request->body, $actor);
        return JsonResponse::make(ApiResponse::success('Tabela atualizada com sucesso.', $table->toArray()));
    }

    public function delete(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $this->service->deletePaymentTable((string) $request->attribute('uuid'), $actor);
        return JsonResponse::make(ApiResponse::success('Tabela removida com sucesso.'));
    }
}
