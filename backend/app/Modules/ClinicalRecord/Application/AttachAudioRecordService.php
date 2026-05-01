<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Application;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\ValidationException;
use App\Modules\ACL\Application\PermissionService;
use App\Core\Support\Uuid;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ClinicalRecord\Infrastructure\Models\AudioRecord;
use App\Modules\Files\Infrastructure\Models\FileRecord;

final class AttachAudioRecordService
{
    private const MAX_AUDIO_SIZE_BYTES = 20 * 1024 * 1024; // 20MB

    public function __construct(
        private readonly AuditService $audit = new AuditService(),
        private readonly PermissionService $permissions = new PermissionService()
    ) {
    }

    public function execute(string $attendanceUuid, array $data, User $authUser): AudioRecord
    {
        /** @var Attendance|null $attendance */
        $attendance = Attendance::query()->where('uuid', $attendanceUuid)->whereNull('deleted_at')->first();
        if ($attendance === null) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'attendance_uuid',
                'message' => 'Atendimento não encontrado.',
            ]]);
        }

        if (in_array((string) $attendance->status, ['finalizado', 'cancelado', 'falta'], true)) {
            throw new ConflictException('Atendimento em estado terminal não permite anexar áudio.');
        }

        if (! $this->canManageAttendance($authUser, $attendance->professional_uuid)) {
            throw new AuthorizationException('Sem permissão para anexar áudio neste atendimento.');
        }

        $fileUuid = trim((string) ($data['file_uuid'] ?? ''));
        if ($fileUuid === '') {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'file_uuid',
                'message' => 'Arquivo de áudio é obrigatório.',
            ]]);
        }

        /** @var FileRecord|null $file */
        $file = FileRecord::query()->where('uuid', $fileUuid)->whereNull('deleted_at')->first();
        if ($file === null || !str_starts_with((string) $file->mime_type, 'audio/')) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'file_uuid',
                'message' => 'Arquivo informado não é um áudio válido.',
            ]]);
        }

        if ((int) $file->size_bytes > self::MAX_AUDIO_SIZE_BYTES) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'file_uuid',
                'message' => 'Áudio acima do limite de 20MB.',
            ]]);
        }

        $audio = AudioRecord::query()->create([
            'uuid' => Uuid::v4(),
            'patient_uuid' => $attendance->patient_uuid,
            'professional_uuid' => $attendance->professional_uuid,
            'attendance_uuid' => $attendanceUuid,
            'title' => trim((string) ($data['title'] ?? 'Áudio clínico')),
            'file_uuid' => $fileUuid,
            'duration_seconds' => isset($data['duration_seconds']) ? (int) $data['duration_seconds'] : null,
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('audio_record.created', (string) $authUser->uuid, ['audio_record_uuid' => $audio->uuid]);

        return $audio;
    }

    private function canManageAttendance(User $user, string $attendanceProfessionalUuid): bool
    {
        if (
            $this->permissions->has($user, 'attendance.update_all')
            || $this->permissions->has($user, 'audio_record.delete')
        ) {
            return true;
        }

        return $user->professional_uuid !== null
            && (string) $user->professional_uuid === $attendanceProfessionalUuid;
    }
}
