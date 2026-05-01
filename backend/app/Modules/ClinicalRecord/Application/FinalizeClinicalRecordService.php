<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Application;

use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\ValidationException;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ClinicalRecord\Infrastructure\Models\ClinicalRecord;

final class FinalizeClinicalRecordService
{
    public function __construct(
        private readonly ClinicalRecordSupport $support = new ClinicalRecordSupport(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $uuid, User $authUser): ClinicalRecord
    {
        $record = $this->support->findRecord($uuid);
        $this->support->assertCanEdit($authUser, $record);

        if ((string) $record->status === 'finalizado') {
            throw new ConflictException('Registro já finalizado.');
        }

        if (trim((string) ($record->content_markdown ?? '')) === '') {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'content_markdown',
                'message' => 'Conteúdo é obrigatório para finalizar.',
            ]]);
        }

        $record->status = 'finalizado';
        $record->finalized_at = date('Y-m-d H:i:s');
        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        $this->audit->log(
            'clinical_record.finalized',
            (string) $authUser->uuid,
            ['clinical_record_uuid' => $record->uuid]
        );

        return $record;
    }
}
