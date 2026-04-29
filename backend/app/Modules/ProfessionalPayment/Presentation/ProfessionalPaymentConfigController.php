<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\ProfessionalPayment\Application\AssignPaymentConfigToProfessionalService;
use App\Modules\ProfessionalPayment\Application\ProfessionalPaymentService;
use App\Modules\ProfessionalPayment\Application\ResolveProfessionalPaymentRuleService;
use App\Modules\ProfessionalPayment\Application\SimulateProfessionalPayoutService;

final class ProfessionalPaymentConfigController
{
    private readonly ProfessionalPaymentService $service;
    private readonly AssignPaymentConfigToProfessionalService $assignService;
    private readonly ResolveProfessionalPaymentRuleService $resolveService;
    private readonly SimulateProfessionalPayoutService $simulateService;

    public function __construct()
    {
        $this->service = new ProfessionalPaymentService();
        $this->assignService = new AssignPaymentConfigToProfessionalService();
        $this->resolveService = new ResolveProfessionalPaymentRuleService();
        $this->simulateService = new SimulateProfessionalPayoutService();
    }

    public function index(Request $request): array
    {
        $professionalUuid = (string) $request->attribute('uuid');
        $data = $this->service->listProfessionalPaymentConfigs($professionalUuid, $request->query);
        return JsonResponse::make(ApiResponse::success(data: $data));
    }

    public function store(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $professionalUuid = (string) $request->attribute('uuid');
        $config = $this->assignService->execute($professionalUuid, $request->body, $actor);

        return JsonResponse::make(ApiResponse::success('Configuração criada com sucesso.', $config->toArray()), 201);
    }

    public function show(Request $request): array
    {
        $config = $this->service->getProfessionalPaymentConfig((string) $request->attribute('uuid'));
        return JsonResponse::make(ApiResponse::success(data: $config->toArray()));
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $config = $this->service->updateProfessionalPaymentConfig(
            (string) $request->attribute('uuid'),
            $request->body,
            $actor
        );
        return JsonResponse::make(ApiResponse::success('Configuração atualizada com sucesso.', $config->toArray()));
    }

    public function delete(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $this->service->deleteProfessionalPaymentConfig((string) $request->attribute('uuid'), $actor);
        return JsonResponse::make(ApiResponse::success('Configuração removida com sucesso.'));
    }

    public function resolveRule(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $professionalUuid = (string) $request->attribute('uuid');
        $date = (string) ($request->query['date'] ?? date('Y-m-d'));
        $resolved = $this->resolveService->execute($professionalUuid, $date, $actor);

        return JsonResponse::make(ApiResponse::success(data: $resolved));
    }

    public function simulate(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $professionalUuid = (string) $request->attribute('uuid');
        $result = $this->simulateService->execute($professionalUuid, $request->body, $actor);

        return JsonResponse::make(ApiResponse::success('Simulação realizada com sucesso.', $result));
    }
}
