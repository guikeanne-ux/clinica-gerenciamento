<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Application;

use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ClinicalRecord\Infrastructure\Models\ClinicalRecord;

final class CreateClinicalRecordComplementService
{
    public function __construct(
        private readonly ClinicalRecordSupport $support = new ClinicalRecordSupport(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $uuid, array $data, User $authUser): ClinicalRecord
    {
        $origin = $this->support->findRecord($uuid);
        $this->support->assertCanView($authUser, $origin);

        $record = ClinicalRecord::query()->create([
            'uuid' => Uuid::v4(),
            'patient_uuid' => $origin->patient_uuid,
            'professional_uuid' => $origin->professional_uuid,
            'attendance_uuid' => $origin->attendance_uuid,
            'record_type' => $origin->record_type,
            'title' => trim((string) ($data['title'] ?? 'Complemento: ' . $origin->title)),
            'content_markdown' => (string) ($data['content_markdown'] ?? ''),
            'status' => 'rascunho',
            'version' => ((int) $origin->version) + 1,
            'created_by_user_uuid' => (string) $authUser->uuid,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('clinical_record.complement_created', (string) $authUser->uuid, [
            'clinical_record_uuid' => $record->uuid,
            'origin_record_uuid' => $origin->uuid,
        ]);

        return $record;
    }
}
