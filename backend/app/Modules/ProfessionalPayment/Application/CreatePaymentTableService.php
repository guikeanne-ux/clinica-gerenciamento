<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Application;

use App\Modules\ProfessionalPayment\Infrastructure\Models\PaymentTable;

final class CreatePaymentTableService
{
    public function __construct(private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService())
    {
    }

    public function execute(array $data, string $actor): PaymentTable
    {
        return $this->service->createPaymentTable($data, $actor);
    }
}
