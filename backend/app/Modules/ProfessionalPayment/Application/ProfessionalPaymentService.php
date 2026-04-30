<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Application;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\ErrorCode;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Person\Infrastructure\Models\Professional;
use App\Modules\ProfessionalPayment\Domain\PaymentRuleSnapshot;
use App\Modules\ProfessionalPayment\Infrastructure\Models\PaymentTable;
use App\Modules\ProfessionalPayment\Infrastructure\Models\PaymentTableItem;
use App\Modules\ProfessionalPayment\Infrastructure\Models\ProfessionalPaymentConfig;

final class ProfessionalPaymentService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function listPaymentTables(array $query): array
    {
        $qb = PaymentTable::query()->whereNull('deleted_at');
        return $this->applyListFilters($qb, $query, ['name', 'description']);
    }

    public function createPaymentTable(array $data, string $actor): PaymentTable
    {
        $this->validatePaymentTable($data);

        $table = PaymentTable::query()->create([
            'uuid' => Uuid::v4(),
            'name' => (string) $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'calculation_type' => (string) $data['calculation_type'],
            'default_percentage' => $data['default_percentage'] ?? null,
            'default_fixed_amount' => $data['default_fixed_amount'] ?? null,
            'effective_start_date' => (string) $data['effective_start_date'],
            'effective_end_date' => $data['effective_end_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('payment_tables.created', $actor, ['payment_table_uuid' => $table->uuid]);
        return $table;
    }

    public function getPaymentTable(string $uuid): PaymentTable
    {
        /** @var PaymentTable|null $table */
        $table = PaymentTable::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($table === null) {
            throw new NotFoundException('Tabela de pagamento não encontrada.');
        }

        return $table;
    }

    public function updatePaymentTable(string $uuid, array $data, string $actor): PaymentTable
    {
        $table = $this->getPaymentTable($uuid);
        $payload = array_merge($table->toArray(), $data);
        $this->validatePaymentTable($payload);

        $table->fill($data);
        $table->updated_at = date('Y-m-d H:i:s');
        $table->save();

        $this->audit->log('payment_tables.updated', $actor, ['payment_table_uuid' => $uuid]);
        return $table;
    }

    public function deletePaymentTable(string $uuid, string $actor): void
    {
        $table = $this->getPaymentTable($uuid);
        $table->status = 'inactive';
        $table->deleted_at = date('Y-m-d H:i:s');
        $table->save();

        $this->audit->log('payment_tables.deleted', $actor, ['payment_table_uuid' => $uuid]);
    }

    public function listPaymentTableItems(string $paymentTableUuid, array $query): array
    {
        $this->getPaymentTable($paymentTableUuid);
        $qb = PaymentTableItem::query()->where('payment_table_uuid', $paymentTableUuid)->whereNull('deleted_at');
        return $this->applyListFilters($qb, $query, ['specialty', 'appointment_type', 'procedure_code']);
    }

    public function createPaymentTableItem(string $paymentTableUuid, array $data, string $actor): PaymentTableItem
    {
        $this->getPaymentTable($paymentTableUuid);
        $this->validatePaymentTableItem($data);

        $item = PaymentTableItem::query()->create([
            'uuid' => Uuid::v4(),
            'payment_table_uuid' => $paymentTableUuid,
            'specialty' => $data['specialty'] ?? null,
            'appointment_type' => $data['appointment_type'] ?? null,
            'health_plan_uuid' => $data['health_plan_uuid'] ?? null,
            'procedure_code' => $data['procedure_code'] ?? null,
            'fixed_value' => $data['fixed_value'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'threshold_quantity' => $data['threshold_quantity'] ?? null,
            'extra_value' => $data['extra_value'] ?? null,
            'rules_json' => isset($data['rules_json']) ? json_encode($data['rules_json']) : null,
            'effective_start_date' => (string) $data['effective_start_date'],
            'effective_end_date' => $data['effective_end_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('payment_table_items.created', $actor, ['payment_table_item_uuid' => $item->uuid]);
        return $item;
    }

    public function getPaymentTableItem(string $uuid): PaymentTableItem
    {
        /** @var PaymentTableItem|null $item */
        $item = PaymentTableItem::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($item === null) {
            throw new NotFoundException('Item da tabela não encontrado.');
        }

        return $item;
    }

    public function updatePaymentTableItem(string $uuid, array $data, string $actor): PaymentTableItem
    {
        $item = $this->getPaymentTableItem($uuid);
        $payload = array_merge($item->toArray(), $data);
        $this->validatePaymentTableItem($payload);

        if (array_key_exists('rules_json', $data)) {
            $data['rules_json'] = json_encode($data['rules_json']);
        }

        $item->fill($data);
        $item->updated_at = date('Y-m-d H:i:s');
        $item->save();

        $this->audit->log('payment_table_items.updated', $actor, ['payment_table_item_uuid' => $uuid]);
        return $item;
    }

    public function deletePaymentTableItem(string $uuid, string $actor): void
    {
        $item = $this->getPaymentTableItem($uuid);
        $item->deleted_at = date('Y-m-d H:i:s');
        $item->save();

        $this->audit->log('payment_table_items.deleted', $actor, ['payment_table_item_uuid' => $uuid]);
    }

    public function listProfessionalPaymentConfigs(string $professionalUuid, array $query): array
    {
        $this->assertProfessionalExists($professionalUuid);
        $qb = ProfessionalPaymentConfig::query()
            ->where('professional_uuid', $professionalUuid)
            ->whereNull('deleted_at');

        return $this->applyListFilters($qb, $query, ['payment_mode', 'notes']);
    }

    public function createProfessionalPaymentConfig(
        string $professionalUuid,
        array $data,
        string $actor
    ): ProfessionalPaymentConfig {
        $this->assertProfessionalExists($professionalUuid);
        $this->validateProfessionalPaymentConfig($professionalUuid, $data);

        $config = ProfessionalPaymentConfig::query()->create([
            'uuid' => Uuid::v4(),
            'professional_uuid' => $professionalUuid,
            'payment_mode' => (string) $data['payment_mode'],
            'payment_table_uuid' => $data['payment_table_uuid'] ?? null,
            'fixed_monthly_amount' => $data['fixed_monthly_amount'] ?? null,
            'fixed_per_attendance_amount' => $data['fixed_per_attendance_amount'] ?? null,
            'hybrid_base_amount' => $data['hybrid_base_amount'] ?? null,
            'hybrid_threshold_quantity' => $data['hybrid_threshold_quantity'] ?? null,
            'hybrid_extra_amount_per_attendance' => $data['hybrid_extra_amount_per_attendance'] ?? null,
            'effective_start_date' => (string) $data['effective_start_date'],
            'effective_end_date' => $data['effective_end_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('professional_payment_configs.created', $actor, [
            'professional_payment_config_uuid' => $config->uuid,
            'professional_uuid' => $professionalUuid,
        ]);

        return $config;
    }

    public function getProfessionalPaymentConfig(string $uuid): ProfessionalPaymentConfig
    {
        /** @var ProfessionalPaymentConfig|null $config */
        $config = ProfessionalPaymentConfig::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($config === null) {
            throw new NotFoundException('Configuração de pagamento não encontrada.');
        }

        return $config;
    }

    public function updateProfessionalPaymentConfig(string $uuid, array $data, string $actor): ProfessionalPaymentConfig
    {
        $config = $this->getProfessionalPaymentConfig($uuid);
        $payload = array_merge($config->toArray(), $data);

        $this->validateProfessionalPaymentConfig($config->professional_uuid, $payload, $uuid);

        $config->fill($data);
        $config->updated_at = date('Y-m-d H:i:s');
        $config->save();

        $this->audit->log('professional_payment_configs.updated', $actor, [
            'professional_payment_config_uuid' => $uuid,
            'professional_uuid' => $config->professional_uuid,
        ]);

        return $config;
    }

    public function deleteProfessionalPaymentConfig(string $uuid, string $actor): void
    {
        $config = $this->getProfessionalPaymentConfig($uuid);
        $config->status = 'inactive';
        $config->deleted_at = date('Y-m-d H:i:s');
        $config->save();

        $this->audit->log('professional_payment_configs.deleted', $actor, [
            'professional_payment_config_uuid' => $uuid,
            'professional_uuid' => $config->professional_uuid,
        ]);
    }

    public function resolveProfessionalPaymentRule(string $professionalUuid, string $date, ?string $actor = null): array
    {
        $this->assertProfessionalExists($professionalUuid);

        $config = $this->findActiveConfigForDate($professionalUuid, $date);
        if ($config === null) {
            throw new NotFoundException('Nenhuma configuração vigente encontrada para a data informada.');
        }

        $valuesUsed = [
            'fixed_per_attendance_amount' => $config->fixed_per_attendance_amount,
            'fixed_monthly_amount' => $config->fixed_monthly_amount,
            'hybrid_base_amount' => $config->hybrid_base_amount,
            'hybrid_threshold_quantity' => $config->hybrid_threshold_quantity,
            'hybrid_extra_amount_per_attendance' => $config->hybrid_extra_amount_per_attendance,
        ];

        $calculationBasis = ['source' => 'professional_payment_config'];

        if ($config->payment_mode === 'fixed_per_attendance' && $config->fixed_per_attendance_amount === null) {
            $valueFromTable = $this->resolveFixedPerAttendanceFromTable($config, $date);
            $valuesUsed['fixed_per_attendance_amount'] = $valueFromTable;
            $calculationBasis = ['source' => 'payment_table_item'];
        }

        $snapshot = new PaymentRuleSnapshot(
            professional_uuid: $professionalUuid,
            payment_config_uuid: $config->uuid,
            payment_table_uuid: $config->payment_table_uuid,
            payment_mode: $config->payment_mode,
            calculation_basis: $calculationBasis,
            values_used: $valuesUsed,
            effective_start_date: (string) $config->effective_start_date,
            effective_end_date: $config->effective_end_date,
            generated_at: date('c')
        );

        if ($actor !== null) {
            $this->audit->log('professional_payment_rules.resolved', $actor, [
                'professional_uuid' => $professionalUuid,
                'professional_payment_config_uuid' => $config->uuid,
                'reference_date' => $date,
            ]);
        }

        return [
            'payment_config' => $config->toArray(),
            'snapshot' => $snapshot->toArray(),
            'hybrid_policy_note' => 'No modo híbrido, o adicional incide apenas sobre atendimentos '
                . 'excedentes ao limite.',
        ];
    }

    public function simulateProfessionalPayout(string $professionalUuid, array $data, string $actor): array
    {
        $referenceDate = (string) (
            $data['reference_date'] ?? (($data['reference_month'] ?? date('Y-m')) . '-01')
        );
        $attendances = (int) ($data['attendances_count'] ?? 0);

        if ($attendances < 0) {
            throw new ValidationException('Quantidade de atendimentos inválida.', [['field' => 'attendances_count', 'message' => 'Quantidade de atendimentos inválida.']]);
        }

        $resolved = $this->resolveProfessionalPaymentRule($professionalUuid, $referenceDate);
        $config = $resolved['payment_config'];
        $mode = (string) $config['payment_mode'];

        $total = 0.0;
        $details = [];

        if ($mode === 'fixed_per_attendance') {
            $amount = (float) ($resolved['snapshot']['values_used']['fixed_per_attendance_amount'] ?? 0);
            $total = $attendances * $amount;
            $details = ['amount_per_attendance' => $amount];
        } elseif ($mode === 'fixed_monthly') {
            $total = (float) ($config['fixed_monthly_amount'] ?? 0);
            $details = ['fixed_monthly_amount' => $total];
        } else {
            $base = (float) ($config['hybrid_base_amount'] ?? 0);
            $threshold = (int) ($config['hybrid_threshold_quantity'] ?? 0);
            $extra = (float) ($config['hybrid_extra_amount_per_attendance'] ?? 0);
            $exceeded = max(0, $attendances - $threshold);
            $extraTotal = $exceeded * $extra;
            $total = $base + $extraTotal;
            $details = [
                'hybrid_base_amount' => $base,
                'hybrid_threshold_quantity' => $threshold,
                'hybrid_extra_amount_per_attendance' => $extra,
                'exceeded_attendances' => $exceeded,
                'exceeded_total' => $extraTotal,
            ];
        }

        $payload = [
            'reference_month' => (string) ($data['reference_month'] ?? substr($referenceDate, 0, 7)),
            'reference_date' => $referenceDate,
            'attendances_count' => $attendances,
            'payment_mode' => $mode,
            'calculation_details' => $details,
            'total_amount' => round($total, 2),
            'snapshot' => $resolved['snapshot'],
            'hybrid_policy_note' => $resolved['hybrid_policy_note'],
        ];

        $this->audit->log('professional_payment.simulated', $actor, [
            'professional_uuid' => $professionalUuid,
            'reference_date' => $referenceDate,
            'attendances_count' => $attendances,
            'payment_mode' => $mode,
            'total_amount' => $payload['total_amount'],
        ]);

        return $payload;
    }

    private function validatePaymentTable(array $data): void
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new ValidationException('Nome da tabela é obrigatório.', [['field' => 'name', 'message' => 'Nome da tabela é obrigatório.']]);
        }

        if (! in_array(($data['status'] ?? 'active'), ['active', 'inactive'], true)) {
            throw new ValidationException('Status inválido.', [['field' => 'status', 'message' => 'Status inválido.']]);
        }

        $types = ['fixed_per_attendance', 'fixed_monthly', 'hybrid', 'custom'];
        if (! in_array((string) ($data['calculation_type'] ?? ''), $types, true)) {
            throw new ValidationException('Tipo de cálculo inválido.', [['field' => 'calculation_type', 'message' => 'Tipo de cálculo inválido.']]);
        }

        if (trim((string) ($data['effective_start_date'] ?? '')) === '') {
            throw new ValidationException('Data de início da vigência é obrigatória.', [['field' => 'effective_start_date', 'message' => 'Data de início da vigência é obrigatória.']]);
        }

        $this->assertNonNegative($data['default_fixed_amount'] ?? null, 'default_fixed_amount');
        if (isset($data['default_percentage'])) {
            $percentage = (float) $data['default_percentage'];
            if ($percentage < 0 || $percentage > 100) {
                throw new ValidationException(
                    'default_percentage deve estar entre 0 e 100.',
                    [['field' => 'default_percentage', 'message' => 'default_percentage deve estar entre 0 e 100.']]
                );
            }
        }
    }

    private function validatePaymentTableItem(array $data): void
    {
        if (trim((string) ($data['effective_start_date'] ?? '')) === '') {
            throw new ValidationException('Data de início da vigência do item é obrigatória.', [['field' => 'effective_start_date', 'message' => 'Data de início da vigência do item é obrigatória.']]);
        }

        $this->assertNonNegative($data['fixed_value'] ?? null, 'fixed_value');
        $this->assertNonNegative($data['extra_value'] ?? null, 'extra_value');

        if (isset($data['percentage'])) {
            $percentage = (float) $data['percentage'];
            if ($percentage < 0 || $percentage > 100) {
                throw new ValidationException('percentage deve estar entre 0 e 100.', [['field' => 'percentage', 'message' => 'percentage deve estar entre 0 e 100.']]);
            }
        }

        if (isset($data['duration_minutes']) && (int) $data['duration_minutes'] <= 0) {
            throw new ValidationException('duration_minutes deve ser maior que zero.', [['field' => 'duration_minutes', 'message' => 'duration_minutes deve ser maior que zero.']]);
        }

        if (isset($data['threshold_quantity']) && (int) $data['threshold_quantity'] < 0) {
            throw new ValidationException('threshold_quantity deve ser maior ou igual a zero.', [['field' => 'threshold_quantity', 'message' => 'threshold_quantity deve ser maior ou igual a zero.']]);
        }
    }

    private function validateProfessionalPaymentConfig(
        string $professionalUuid,
        array $data,
        ?string $exceptUuid = null
    ): void {
        $modes = ['fixed_per_attendance', 'fixed_monthly', 'hybrid'];
        $mode = (string) ($data['payment_mode'] ?? '');

        if (! in_array($mode, $modes, true)) {
            throw new ValidationException('Modo de pagamento inválido.', [['field' => 'payment_mode', 'message' => 'Modo de pagamento inválido.']]);
        }

        if (! in_array(($data['status'] ?? 'active'), ['active', 'inactive'], true)) {
            throw new ValidationException('Status inválido.', [['field' => 'status', 'message' => 'Status inválido.']]);
        }

        if (trim((string) ($data['effective_start_date'] ?? '')) === '') {
            throw new ValidationException('Data de início da vigência é obrigatória.', [['field' => 'effective_start_date', 'message' => 'Data de início da vigência é obrigatória.']]);
        }

        $this->assertNonNegative($data['fixed_monthly_amount'] ?? null, 'fixed_monthly_amount');
        $this->assertNonNegative($data['fixed_per_attendance_amount'] ?? null, 'fixed_per_attendance_amount');
        $this->assertNonNegative($data['hybrid_base_amount'] ?? null, 'hybrid_base_amount');
        $this->assertNonNegative(
            $data['hybrid_extra_amount_per_attendance'] ?? null,
            'hybrid_extra_amount_per_attendance'
        );

        if (isset($data['hybrid_threshold_quantity']) && (int) $data['hybrid_threshold_quantity'] < 0) {
            throw new ValidationException('hybrid_threshold_quantity deve ser maior ou igual a zero.', [['field' => 'hybrid_threshold_quantity', 'message' => 'hybrid_threshold_quantity deve ser maior ou igual a zero.']]);
        }

        if (($data['payment_table_uuid'] ?? null) !== null) {
            $this->getPaymentTable((string) $data['payment_table_uuid']);
        }

        if ($mode === 'fixed_monthly' && ! isset($data['fixed_monthly_amount'])) {
            throw new ValidationException('fixed_monthly_amount é obrigatório para modo fixed_monthly.', [['field' => 'fixed_monthly_amount', 'message' => 'fixed_monthly_amount é obrigatório para modo fixed_monthly.']]);
        }

        if ($mode === 'hybrid') {
            $required = [
                'hybrid_base_amount',
                'hybrid_threshold_quantity',
                'hybrid_extra_amount_per_attendance',
            ];
            foreach ($required as $field) {
                if (! isset($data[$field])) {
                    throw new ValidationException($field . ' é obrigatório para modo hybrid.', [['field' => $field, 'message' => $field . ' é obrigatório para modo hybrid.']]);
                }
            }
        }

        if (($data['status'] ?? 'active') === 'active') {
            $start = (string) $data['effective_start_date'];
            $end = $data['effective_end_date'] ?? null;
            $this->assertNoActiveOverlap($professionalUuid, $start, is_string($end) ? $end : null, $exceptUuid);
        }
    }

    private function assertNoActiveOverlap(
        string $professionalUuid,
        string $newStart,
        ?string $newEnd,
        ?string $exceptUuid
    ): void {
        $query = ProfessionalPaymentConfig::query()
            ->where('professional_uuid', $professionalUuid)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($newStart, $newEnd): void {
                $q->where(function ($q2) use ($newStart): void {
                    $q2->whereNull('effective_end_date')->where('effective_start_date', '<=', $newStart);
                })->orWhere(function ($q2) use ($newStart): void {
                    $q2->where('effective_start_date', '<=', $newStart)->where('effective_end_date', '>=', $newStart);
                });

                if ($newEnd !== null) {
                    $q->orWhere(function ($q2) use ($newEnd): void {
                        $q2->where('effective_start_date', '<=', $newEnd)
                            ->where(function ($q3) use ($newEnd): void {
                                $q3->whereNull('effective_end_date')
                                    ->orWhere('effective_end_date', '>=', $newEnd);
                            });
                    })->orWhere(function ($q2) use ($newStart, $newEnd): void {
                        $q2->where('effective_start_date', '>=', $newStart)
                            ->where('effective_start_date', '<=', $newEnd);
                    });
                }
            });

        if ($exceptUuid !== null) {
            $query->where('uuid', '!=', $exceptUuid);
        }

        if ($query->first() !== null) {
            throw new ConflictException(
                'Já existe configuração ativa vigente para o profissional na data/período informado.',
                ErrorCode::CONFLICT
            );
        }
    }

    private function findActiveConfigForDate(string $professionalUuid, string $date): ?ProfessionalPaymentConfig
    {
        /** @var ProfessionalPaymentConfig|null $config */
        $config = ProfessionalPaymentConfig::query()
            ->where('professional_uuid', $professionalUuid)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->where('effective_start_date', '<=', $date)
            ->where(function ($q) use ($date): void {
                $q->whereNull('effective_end_date')->orWhere('effective_end_date', '>=', $date);
            })
            ->orderByDesc('effective_start_date')
            ->first();

        return $config;
    }

    private function resolveFixedPerAttendanceFromTable(ProfessionalPaymentConfig $config, string $date): float
    {
        if ($config->payment_table_uuid === null) {
            return 0.0;
        }

        $table = $this->getPaymentTable($config->payment_table_uuid);

        /** @var PaymentTableItem|null $item */
        $item = PaymentTableItem::query()
            ->where('payment_table_uuid', $config->payment_table_uuid)
            ->whereNull('deleted_at')
            ->where('effective_start_date', '<=', $date)
            ->where(function ($q) use ($date): void {
                $q->whereNull('effective_end_date')->orWhere('effective_end_date', '>=', $date);
            })
            ->orderByDesc('effective_start_date')
            ->first();

        if ($item !== null && $item->fixed_value !== null) {
            return (float) $item->fixed_value;
        }

        return (float) ($table->default_fixed_amount ?? 0);
    }

    private function assertProfessionalExists(string $professionalUuid): void
    {
        /** @var Professional|null $professional */
        $professional = Professional::query()->where('uuid', $professionalUuid)->whereNull('deleted_at')->first();
        if ($professional === null) {
            throw new NotFoundException('Profissional não encontrado.');
        }
    }

    private function assertNonNegative(mixed $value, string $field): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ((float) $value < 0) {
            throw new ValidationException($field . ' não pode ser negativo.', [['field' => $field, 'message' => $field . ' não pode ser negativo.']]);
        }
    }

    private function applyListFilters($qb, array $query, array $fields): array
    {
        $search = trim((string) ($query['search'] ?? ''));
        if ($search !== '') {
            $qb->where(function ($q) use ($search, $fields): void {
                foreach ($fields as $i => $field) {
                    if ($i === 0) {
                        $q->where($field, 'like', '%' . $search . '%');
                    } else {
                        $q->orWhere($field, 'like', '%' . $search . '%');
                    }
                }
            });
        }

        if (($query['status'] ?? '') !== '') {
            $qb->where('status', $query['status']);
        }

        $sort = (string) ($query['sort'] ?? 'created_at');
        $direction = strtolower((string) ($query['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $total = (clone $qb)->count();
        $items = $qb->orderBy($sort, $direction)->forPage($page, $perPage)->get()->toArray();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }
}
