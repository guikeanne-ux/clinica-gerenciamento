<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Application;

use App\Core\Exceptions\ValidationException;
use App\Core\Support\Uuid;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ClinicalRecord\Infrastructure\Models\ClinicalRecord;

final class CreateClinicalRecordService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function execute(string $attendanceUuid, array $data, User $authUser): ClinicalRecord
    {
        /** @var Attendance|null $attendance */
        $attendance = Attendance::query()->where('uuid', $attendanceUuid)->whereNull('deleted_at')->first();
        if ($attendance === null) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'attendance_uuid',
                'message' => 'Atendimento não encontrado.',
            ]]);
        }

        $recordType = trim((string) ($data['record_type'] ?? ''));
        if (! in_array($recordType, ['evolucao', 'anamnese', 'prontuario_livre'], true)) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'record_type',
                'message' => 'Tipo de registro inválido.',
            ]]);
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = ucfirst(str_replace('_', ' ', $recordType));
        }

        $record = ClinicalRecord::query()->create([
            'uuid' => Uuid::v4(),
            'patient_uuid' => $attendance->patient_uuid,
            'professional_uuid' => $attendance->professional_uuid,
            'attendance_uuid' => $attendanceUuid,
            'record_type' => $recordType,
            'title' => $title,
            'content_markdown' => trim((string) ($data['content_markdown'] ?? '')),
            'status' => 'rascunho',
            'version' => 1,
            'created_by_user_uuid' => (string) $authUser->uuid,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log(
            'clinical_record.created',
            (string) $authUser->uuid,
            ['clinical_record_uuid' => $record->uuid]
        );

        return $record;
    }
}
