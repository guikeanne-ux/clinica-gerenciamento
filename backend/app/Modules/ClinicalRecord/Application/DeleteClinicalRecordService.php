<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Application;

use App\Core\Exceptions\ConflictException;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;

final class DeleteClinicalRecordService
{
    public function __construct(
        private readonly ClinicalRecordSupport $support = new ClinicalRecordSupport(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $recordUuid, User $authUser): void
    {
        $record = $this->support->findRecord($recordUuid);
        $this->support->assertCanEdit($authUser, $record);

        /** @var Attendance|null $attendance */
        $attendance = Attendance::query()
            ->where('uuid', (string) $record->attendance_uuid)
            ->whereNull('deleted_at')
            ->first();

        if ($attendance !== null && in_array((string) $attendance->status, ['finalizado', 'cancelado', 'falta'], true)) {
            throw new ConflictException('Atendimento encerrado não permite excluir registro clínico.');
        }

        if ((string) $record->status === 'finalizado') {
            throw new ConflictException('Registro finalizado é imutável e não pode ser excluído.');
        }

        $record->delete();

        $this->audit->log(
            'clinical_record.deleted',
            (string) $authUser->uuid,
            ['clinical_record_uuid' => (string) $record->uuid]
        );
    }
}
