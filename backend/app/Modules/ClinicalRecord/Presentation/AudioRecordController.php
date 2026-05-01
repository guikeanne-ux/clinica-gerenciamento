<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Presentation;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\ClinicalRecord\Application\AttachAudioRecordService;
use App\Modules\ClinicalRecord\Infrastructure\Models\AudioRecord;
use App\Modules\Files\Infrastructure\Models\FileRecord;

final class AudioRecordController
{
    public function __construct(
        private readonly AttachAudioRecordService $attachService = new AttachAudioRecordService(),
        private readonly PermissionService $permissions = new PermissionService()
    ) {
    }

    public function store(Request $request): array
    {
        $audio = $this->attachService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );
        return JsonResponse::make(ApiResponse::success('Áudio anexado com sucesso.', $audio->toArray()), 201);
    }

    public function show(Request $request): array
    {
        /** @var AudioRecord|null $audio */
        $audio = AudioRecord::query()
            ->where('uuid', (string) $request->attribute('uuid'))
            ->whereNull('deleted_at')
            ->first();

        if ($audio === null) {
            return JsonResponse::make(ApiResponse::error('Áudio não encontrado.'), 404);
        }

        $this->assertCanViewOrManage($request->attribute('auth_user'), $audio->professional_uuid);

        $data = $audio->toArray();
        $data['file'] = $this->resolveFileData((string) $audio->file_uuid);
        return JsonResponse::make(ApiResponse::success(data: $data));
    }

    public function indexByAttendance(Request $request): array
    {
        /** @var Attendance|null $attendance */
        $attendance = Attendance::query()
            ->where('uuid', (string) $request->attribute('uuid'))
            ->whereNull('deleted_at')
            ->first();

        if ($attendance === null) {
            return JsonResponse::make(ApiResponse::error('Atendimento não encontrado.'), 404);
        }

        $this->assertCanViewOrManage($request->attribute('auth_user'), (string) $attendance->professional_uuid);

        /** @var array<int, AudioRecord> $records */
        $records = AudioRecord::query()
            ->where('attendance_uuid', (string) $attendance->uuid)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->all();

        $items = array_map(function (AudioRecord $record): array {
            $data = $record->toArray();
            $data['file'] = $this->resolveFileData((string) $record->file_uuid);

            return $data;
        }, $records);

        return JsonResponse::make(ApiResponse::success(data: ['items' => $items]));
    }

    public function delete(Request $request): array
    {
        /** @var AudioRecord|null $audio */
        $audio = AudioRecord::query()
            ->where('uuid', (string) $request->attribute('uuid'))
            ->whereNull('deleted_at')
            ->first();

        if ($audio === null) {
            return JsonResponse::make(ApiResponse::error('Áudio não encontrado.'), 404);
        }

        $this->assertCanViewOrManage($request->attribute('auth_user'), (string) $audio->professional_uuid, true);
        $audio->delete();

        return JsonResponse::make(ApiResponse::success('Áudio removido com sucesso.'));
    }

    private function assertCanViewOrManage(User $user, string $professionalUuid, bool $isDelete = false): void
    {
        if (
            $this->permissions->has($user, 'attendance.update_all')
            || $this->permissions->has($user, 'clinical_record.view_all')
        ) {
            return;
        }

        if ($isDelete && $this->permissions->has($user, 'audio_record.delete')) {
            return;
        }

        if (
            $this->permissions->has($user, 'audio_record.view')
            && $user->professional_uuid !== null
            && (string) $user->professional_uuid === $professionalUuid
        ) {
            return;
        }

        throw new AuthorizationException('Sem permissão para acessar este áudio.');
    }

    private function resolveFileData(string $fileUuid): ?array
    {
        /** @var FileRecord|null $file */
        $file = FileRecord::query()->where('uuid', $fileUuid)->whereNull('deleted_at')->first();
        if ($file === null) {
            return null;
        }

        return [
            'uuid' => (string) $file->uuid,
            'mime_type' => (string) $file->mime_type,
            'original_name' => (string) $file->original_name,
            'size_bytes' => (int) $file->size_bytes,
            'download_url' => '/api/v1/files/' . (string) $file->uuid . '/download',
        ];
    }
}
