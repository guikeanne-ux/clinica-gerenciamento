<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Application;

use App\Modules\ProfessionalPayment\Infrastructure\Models\PaymentTableItem;

final class UpdatePaymentTableItemService
{
    public function __construct(private readonly ProfessionalPaymentService $service = new ProfessionalPaymentService())
    {
    }

    public function execute(string $uuid, array $data, string $actor): PaymentTableItem
    {
        return $this->service->updatePaymentTableItem($uuid, $data, $actor);
    }
}
