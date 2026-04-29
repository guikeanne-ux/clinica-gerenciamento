<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Application;

final class SimulateProfessionalPayoutService
{
    public function __construct(private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService())
    {
    }

    public function execute(string $professionalUuid, array $data, string $actor): array
    {
        return $this->service->simulateProfessionalPayout($professionalUuid, $data, $actor);
    }
}
