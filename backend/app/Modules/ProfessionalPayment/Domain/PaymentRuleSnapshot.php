<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Domain;

final class PaymentRuleSnapshot
{
    public function __construct(
        public readonly string $professional_uuid,
        public readonly string $payment_config_uuid,
        public readonly ?string $payment_table_uuid,
        public readonly string $payment_mode,
        public readonly array $calculation_basis,
        public readonly array $values_used,
        public readonly string $effective_start_date,
        public readonly ?string $effective_end_date,
        public readonly string $generated_at
    ) {
    }

    public function toArray(): array
    {
        return [
            'professional_uuid' => $this->professional_uuid,
            'payment_config_uuid' => $this->payment_config_uuid,
            'payment_table_uuid' => $this->payment_table_uuid,
            'payment_mode' => $this->payment_mode,
            'calculation_basis' => $this->calculation_basis,
            'values_used' => $this->values_used,
            'effective_start_date' => $this->effective_start_date,
            'effective_end_date' => $this->effective_end_date,
            'generated_at' => $this->generated_at,
        ];
    }
}
