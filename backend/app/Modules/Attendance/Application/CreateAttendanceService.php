<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Application;

use App\Core\Exceptions\ValidationException;
use App\Core\Support\Uuid;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ProfessionalPayment\Application\ResolveProfessionalPaymentRuleService;

final class CreateAttendanceService
{
    private readonly ResolveProfessionalPaymentRuleService $paymentRule;

    public function __construct(
        private readonly AttendanceSupport $support = new AttendanceSupport(),
        private readonly AuditService $audit = new AuditService(),
        ?ResolveProfessionalPaymentRuleService $paymentRule = null
    ) {
        $this->paymentRule = $paymentRule ?? new ResolveProfessionalPaymentRuleService();
    }

    public function execute(array $data, User $authUser): Attendance
    {
        $patientUuid = trim((string) ($data['patient_uuid'] ?? ''));
        $professionalUuid = trim((string) ($data['professional_uuid'] ?? ''));

        $this->support->assertUuidExists(
            'patients',
            $patientUuid,
            'patient_uuid',
            'Paciente é obrigatório.'
        );
        $this->support->assertUuidExists(
            'professionals',
            $professionalUuid,
            'professional_uuid',
            'Profissional é obrigatório.'
        );

        $startsAt = $this->nullableString($data['starts_at'] ?? null);
        $endsAt = $this->nullableString($data['ends_at'] ?? null);

        if ($startsAt !== null && $endsAt !== null && strtotime($startsAt) > strtotime($endsAt)) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'starts_at',
                'message' => 'Início não pode ser posterior ao fim.',
            ]]);
        }

        $scheduleEventUuid = $this->nullableString($data['schedule_event_uuid'] ?? null);
        if ($scheduleEventUuid !== null) {
            $this->support->assertUuidExists(
                'schedule_events',
                $scheduleEventUuid,
                'schedule_event_uuid',
                'Evento não encontrado.'
            );
            /** @var Attendance|null $existing */
            $existing = Attendance::query()
                ->where('schedule_event_uuid', $scheduleEventUuid)
                ->whereNull('deleted_at')
                ->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $financialSnapshot = null;
        $calculatedPayoutValue = null;
        $referenceDate = $startsAt !== null ? date('Y-m-d', strtotime($startsAt)) : date('Y-m-d');

        try {
            $resolved = $this->paymentRule->execute($professionalUuid, $referenceDate);
            $financialSnapshot = json_encode($resolved['snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $fixed = $resolved['snapshot']['values_used']['fixed_per_attendance_amount'] ?? null;
            $calculatedPayoutValue = is_numeric($fixed) ? (int) round(((float) $fixed) * 100) : null;
        } catch (\Throwable) {
            $financialSnapshot = json_encode([
                'pending' => true,
                'message' => 'Configuração financeira não encontrada para o período.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $attendance = Attendance::query()->create([
            'uuid' => Uuid::v4(),
            'patient_uuid' => $patientUuid,
            'professional_uuid' => $professionalUuid,
            'original_professional_uuid' => $professionalUuid,
            'schedule_event_uuid' => $scheduleEventUuid,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_minutes' => $this->duration($startsAt, $endsAt),
            'status' => trim((string) ($data['status'] ?? 'rascunho')),
            'attendance_type' => trim((string) ($data['attendance_type'] ?? 'consulta')),
            'modality' => trim((string) ($data['modality'] ?? 'presencial')),
            'financial_table_snapshot_json' => $financialSnapshot,
            'calculated_payout_value' => $calculatedPayoutValue,
            'internal_notes' => $this->nullableString($data['internal_notes'] ?? null),
            'created_by_user_uuid' => (string) $authUser->uuid,
            'updated_by_user_uuid' => (string) $authUser->uuid,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('attendance.created', (string) $authUser->uuid, ['attendance_uuid' => $attendance->uuid]);

        return $attendance;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }

    private function duration(?string $startsAt, ?string $endsAt): ?int
    {
        if ($startsAt === null || $endsAt === null) {
            return null;
        }

        return max(0, (int) round((strtotime($endsAt) - strtotime($startsAt)) / 60));
    }
}
