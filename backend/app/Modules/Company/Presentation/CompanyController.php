<?php

declare(strict_types=1);

namespace App\Modules\Company\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Company\Application\CompanyService;

final class CompanyController
{
    public function __construct(private readonly CompanyService $companyService = new CompanyService())
    {
    }

    public function get(Request $request): array
    {
        $company = $this->companyService->getOrCreate();

        return JsonResponse::make(ApiResponse::success(data: $company->toArray()));
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('auth_user')->uuid;
        $company = $this->companyService->update($request->body, $actor);

        return JsonResponse::make(ApiResponse::success('Empresa atualizada com sucesso.', $company->toArray()));
    }
}
