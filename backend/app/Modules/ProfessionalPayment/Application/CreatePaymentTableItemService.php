<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Application;

use App\Modules\ProfessionalPayment\Infrastructure\Models\PaymentTableItem;

final class CreatePaymentTableItemService
{
    public function __construct(private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService())
    {
    }

    public function execute(string $paymentTableUuid, array $data, string $actor): PaymentTableItem
    {
        return $this->service->createPaymentTableItem($paymentTableUuid, $data, $actor);
    }
}
