<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Application;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\NotFoundException;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ClinicalRecord\Infrastructure\Models\ClinicalRecord;

final class ClinicalRecordSupport
{
    public function __construct(private readonly PermissionService $permissions = new PermissionService())
    {
    }

    public function findRecord(string $uuid): ClinicalRecord
    {
        /** @var ClinicalRecord|null $record */
        $record = ClinicalRecord::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($record === null) {
            throw new NotFoundException('Registro clínico não encontrado.');
        }

        return $record;
    }

    public function assertCanView(User $user, ClinicalRecord $record): void
    {
        if ($this->permissions->has($user, 'clinical_record.view_all')) {
            return;
        }

        if (
            $this->permissions->has($user, 'clinical_record.view_own')
            && $user->professional_uuid !== null
            && (string) $user->professional_uuid === (string) $record->professional_uuid
        ) {
            return;
        }

        throw new AuthorizationException('Sem permissão para visualizar este registro clínico.');
    }

    public function assertCanEdit(User $user, ClinicalRecord $record): void
    {
        if ((string) $record->status === 'finalizado') {
            throw new ConflictException('Registro finalizado é imutável.');
        }

        if ($this->permissions->has($user, 'clinical_record.update_all')) {
            return;
        }

        if (
            $this->permissions->has($user, 'clinical_record.update_own')
            && $user->professional_uuid !== null
            && (string) $user->professional_uuid === (string) $record->professional_uuid
        ) {
            return;
        }

        throw new AuthorizationException('Sem permissão para editar este registro clínico.');
    }
}
