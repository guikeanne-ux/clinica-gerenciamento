<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Application;

use App\Modules\ProfessionalPayment\Infrastructure\Models\ProfessionalPaymentConfig;

final class AssignPaymentConfigToProfessionalService
{
    public function __construct(private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService())
    {
    }

    public function execute(string $professionalUuid, array $data, string $actor): ProfessionalPaymentConfig
    {
        return $this->service->createProfessionalPaymentConfig($professionalUuid, $data, $actor);
    }
}
