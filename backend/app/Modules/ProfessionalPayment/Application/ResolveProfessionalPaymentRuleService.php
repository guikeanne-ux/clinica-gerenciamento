<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Application;

final class ResolveProfessionalPaymentRuleService
{
    public function __construct(private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService())
    {
    }

    public function execute(string $professionalUuid, string $date, ?string $actor = null): array
    {
        return $this->service->resolveProfessionalPaymentRule($professionalUuid, $date, $actor);
    }
}
