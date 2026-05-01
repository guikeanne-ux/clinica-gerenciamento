<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Application;

use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\ValidationException;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ProfessionalPayment\Application\ResolveProfessionalPaymentRuleService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;

final class SubstituteAttendanceProfessionalService
{
    private readonly ResolveProfessionalPaymentRuleService $paymentRule;

    public function __construct(
        private readonly AttendanceSupport $support = new AttendanceSupport(),
        private readonly AuditService $audit = new AuditService(),
        ?ResolveProfessionalPaymentRuleService $paymentRule = null
    ) {
        $this->paymentRule = $paymentRule ?? new ResolveProfessionalPaymentRuleService();
    }

    public function execute(string $attendanceUuid, array $data, User $authUser): array
    {
        $attendance = $this->support->findAttendance($attendanceUuid);
        $this->support->assertCanMutateAttendance($authUser, $attendance);

        if (in_array((string) $attendance->status, ['finalizado', 'cancelado', 'falta'], true)) {
            throw new ConflictException('Não é possível substituir profissional para este status de atendimento.');
        }

        $newProfessionalUuid = trim((string) ($data['professional_uuid'] ?? ''));
        $reason = trim((string) ($data['reason'] ?? ''));

        $this->support->assertUuidExists(
            'professionals',
            $newProfessionalUuid,
            'professional_uuid',
            'Profissional substituto não encontrado.'
        );

        if ($reason === '') {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'reason',
                'message' => 'Motivo da substituição é obrigatório.',
            ]]);
        }

        $currentProfessionalUuid = (string) $attendance->professional_uuid;
        if ($currentProfessionalUuid === $newProfessionalUuid) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'professional_uuid',
                'message' => 'Informe um profissional diferente do atual.',
            ]]);
        }

        $attendance->original_professional_uuid = $attendance->original_professional_uuid ?: $currentProfessionalUuid;
        $attendance->professional_uuid = $newProfessionalUuid;
        $attendance->substituted_professional_uuid = $newProfessionalUuid;
        $attendance->substitution_reason = $reason;
        $attendance->substituted_at = date('Y-m-d H:i:s');
        $attendance->substituted_by_user_uuid = (string) $authUser->uuid;
        $attendance->updated_by_user_uuid = (string) $authUser->uuid;

        $referenceDate = $attendance->starts_at !== null
            ? date('Y-m-d', strtotime((string) $attendance->starts_at))
            : date('Y-m-d');

        try {
            $resolved = $this->paymentRule->execute($newProfessionalUuid, $referenceDate);
            $attendance->financial_table_snapshot_json = json_encode(
                $resolved['snapshot'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $fixed = $resolved['snapshot']['values_used']['fixed_per_attendance_amount'] ?? null;
            $attendance->calculated_payout_value = is_numeric($fixed) ? (int) round(((float) $fixed) * 100) : null;
        } catch (\Throwable) {
            $attendance->financial_table_snapshot_json = json_encode([
                'pending' => true,
                'message' => 'Configuração financeira não encontrada para o substituto.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $attendance->calculated_payout_value = null;
        }

        $attendance->updated_at = date('Y-m-d H:i:s');
        $attendance->save();

        $syncSchedule = array_key_exists('sync_schedule_event_professional', $data)
            ? (bool) $data['sync_schedule_event_professional']
            : true;

        if ($syncSchedule && $attendance->schedule_event_uuid !== null) {
            /** @var ScheduleEvent|null $event */
            $event = ScheduleEvent::query()
                ->where('uuid', (string) $attendance->schedule_event_uuid)
                ->whereNull('deleted_at')
                ->first();

            if ($event !== null) {
                $event->professional_uuid = $newProfessionalUuid;
                $event->updated_by_user_uuid = (string) $authUser->uuid;
                $event->updated_at = date('Y-m-d H:i:s');
                $event->save();
            }
        }

        $this->audit->log('attendance.professional_substituted', (string) $authUser->uuid, [
            'attendance_uuid' => $attendance->uuid,
            'from_professional_uuid' => $currentProfessionalUuid,
            'to_professional_uuid' => $newProfessionalUuid,
            'reason' => $reason,
            'sync_schedule_event_professional' => $syncSchedule,
        ]);

        return [
            'attendance' => $attendance->toArray(),
            'substitution' => [
                'from_professional_uuid' => $currentProfessionalUuid,
                'to_professional_uuid' => $newProfessionalUuid,
                'reason' => $reason,
                'substituted_at' => $attendance->substituted_at,
                'substituted_by_user_uuid' => $attendance->substituted_by_user_uuid,
            ],
        ];
    }
}
