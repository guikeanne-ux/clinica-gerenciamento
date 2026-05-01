<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Application;

use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ClinicalRecord\Infrastructure\Models\ClinicalRecord;

final class UpdateClinicalRecordDraftService
{
    public function __construct(
        private readonly ClinicalRecordSupport $support = new ClinicalRecordSupport(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $uuid, array $data, User $authUser): ClinicalRecord
    {
        $record = $this->support->findRecord($uuid);
        $this->support->assertCanEdit($authUser, $record);

        if (array_key_exists('title', $data)) {
            $title = trim((string) $data['title']);
            $record->title = $title !== '' ? $title : $record->title;
        }

        if (array_key_exists('content_markdown', $data)) {
            $record->content_markdown = (string) $data['content_markdown'];
        }

        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        $this->audit->log(
            'clinical_record.draft_updated',
            (string) $authUser->uuid,
            ['clinical_record_uuid' => $record->uuid]
        );

        return $record;
    }
}
