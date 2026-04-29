<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\ProfessionalPayment\Application\CreatePaymentTableItemService;
use App\Modules\ProfessionalPayment\Application\ProfessionalPaymentService;
use App\Modules\ProfessionalPayment\Application\UpdatePaymentTableItemService;

final class PaymentTableItemController
{
    public function __construct(
        private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService(),
        private readonly CreatePaymentTableItemService $createService = new CreatePaymentTableItemService(),
        private readonly UpdatePaymentTableItemService $updateService = new UpdatePaymentTableItemService()
    ) {
    }

    public function index(Request $request): array
    {
        $tableUuid = (string) $request->attribute('uuid');
        $data = $this->service->listPaymentTableItems($tableUuid, $request->query);
        return JsonResponse::make(ApiResponse::success(data: $data));
    }

    public function store(Request $request): array
    {
        $tableUuid = (string) $request->attribute('uuid');
        $actor = $request->attribute('auth_user')->uuid;
        $item = $this->createService->execute($tableUuid, $request->body, $actor);

        return JsonResponse::make(ApiResponse::success('Item criado com sucesso.', $item->toArray()), 201);
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $item = $this->updateService->execute((string) $request->attribute('uuid'), $request->body, $actor);

        return JsonResponse::make(ApiResponse::success('Item atualizado com sucesso.', $item->toArray()));
    }

    public function delete(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $this->service->deletePaymentTableItem((string) $request->attribute('uuid'), $actor);
        return JsonResponse::make(ApiResponse::success('Item removido com sucesso.'));
    }
}
