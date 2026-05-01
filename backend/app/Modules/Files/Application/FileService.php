<?php

declare(strict_types=1);

namespace App\Modules\Files\Application;

use App\Core\Exceptions\ErrorCode;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\UploadException;
use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Files\Infrastructure\Models\FileRecord;
use App\Modules\Files\Infrastructure\Optimizers\AudioOptimizer;
use App\Modules\Files\Infrastructure\Optimizers\ImageOptimizer;
use App\Modules\Files\Infrastructure\Optimizers\PdfOptimizer;

final class FileService
{
    private const ALLOWED_EXTENSIONS = [
        'png',
        'jpg',
        'jpeg',
        'pdf',
        'mp3',
        'wav',
        'ogg',
        'webm',
        'm4a',
        'aac',
    ];
    private const ALLOWED_MIMES = [
        'image/png',
        'image/jpeg',
        'application/pdf',
        'audio/mpeg',
        'audio/wav',
        'audio/x-wav',
        'audio/ogg',
        'audio/webm',
        'audio/mp4',
        'audio/aac',
        'audio/x-m4a',
    ];
    private const MAX_SIZE = 20971520;

    public function __construct(private readonly AuditService $auditService = new AuditService())
    {
    }

    public function upload(array $data, string $uploadedBy): FileRecord
    {
        $originalName = (string) ($data['original_name'] ?? '');
        $mime = (string) ($data['mime_type'] ?? '');
        $base64 = (string) ($data['content_base64'] ?? '');
        $classification = (string) ($data['classification'] ?? 'documento_empresa');

        if ($originalName === '' || $base64 === '') {
            throw new UploadException('Arquivo inválido.', ErrorCode::INVALID_UPLOAD);
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new UploadException('Extensão não permitida.', ErrorCode::UNSUPPORTED_FILE_TYPE);
        }

        $normalizedMime = $this->normalizeMimeType($mime);
        if (! $this->isAllowedMime($normalizedMime)) {
            throw new UploadException('MIME type não permitido.', ErrorCode::INVALID_MIME_TYPE);
        }

        $content = base64_decode($base64, true);
        if ($content === false) {
            throw new UploadException('Conteúdo inválido.', ErrorCode::INVALID_UPLOAD);
        }

        $size = strlen($content);
        if ($size > self::MAX_SIZE) {
            throw new UploadException('Arquivo acima do limite.', ErrorCode::UPLOAD_TOO_LARGE);
        }

        $optimizedResult = $this->optimizeByMime($normalizedMime, $content);
        $internalName = Uuid::v4() . '.' . $extension;

        $file = FileRecord::query()->create([
            'uuid' => Uuid::v4(),
            'original_name' => $originalName,
            'internal_name' => $internalName,
            'mime_type' => $normalizedMime,
            'extension' => $extension,
            'size_bytes' => strlen($optimizedResult['content']),
            'checksum_hash' => hash('sha256', $optimizedResult['content']),
            'content_blob' => $optimizedResult['content'],
            'optimized' => $optimizedResult['optimized'],
            'related_module' => (string) ($data['related_module'] ?? 'company'),
            'related_entity_type' => (string) ($data['related_entity_type'] ?? 'company'),
            'related_entity_uuid' => $data['related_entity_uuid'] ?? null,
            'uploaded_by_user_uuid' => $uploadedBy,
            'classification' => $classification,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->auditService->log('files.uploaded', $uploadedBy, ['file_uuid' => $file->uuid]);

        return $file;
    }

    public function find(string $uuid, string $actor): FileRecord
    {
        /** @var FileRecord|null $file */
        $file = FileRecord::query()
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (! $file instanceof FileRecord) {
            throw new NotFoundException('Arquivo não encontrado.');
        }

        $this->auditService->log('files.viewed', $actor, ['file_uuid' => $file->uuid]);
        return $file;
    }

    public function download(string $uuid, string $actor): FileRecord
    {
        $file = $this->find($uuid, $actor);
        $this->auditService->log('files.downloaded', $actor, ['file_uuid' => $file->uuid]);
        return $file;
    }

    public function softDelete(string $uuid, string $actor): void
    {
        /** @var FileRecord|null $file */
        $file = FileRecord::query()
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (! $file instanceof FileRecord) {
            throw new NotFoundException('Arquivo não encontrado.');
        }

        $file->status = 'deleted';
        $file->deleted_at = date('Y-m-d H:i:s');
        $file->save();

        $this->auditService->log('files.deleted', $actor, ['file_uuid' => $file->uuid]);
    }

    public function listByRelated(string $module, string $entityType): array
    {
        /** @var array<int, FileRecord> $items */
        $items = FileRecord::query()
            ->where('related_module', $module)
            ->where('related_entity_type', $entityType)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->all();

        $result = [];
        foreach ($items as $file) {
            $result[] = [
                'uuid' => $file->uuid,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'classification' => $file->classification,
                'size_bytes' => $file->size_bytes,
                'created_at' => $file->created_at,
            ];
        }

        return $result;
    }

    /** @return array{content:string,optimized:bool} */
    private function optimizeByMime(string $mime, string $content): array
    {
        if (str_starts_with($mime, 'image/')) {
            return (new ImageOptimizer())->optimize($content, $mime);
        }

        if ($mime === 'application/pdf') {
            return (new PdfOptimizer())->optimize($content, $mime);
        }

        if (str_starts_with($mime, 'audio/')) {
            return (new AudioOptimizer())->optimize($content, $mime);
        }

        return ['content' => $content, 'optimized' => false];
    }

    private function normalizeMimeType(string $mime): string
    {
        $clean = strtolower(trim($mime));
        if ($clean === '') {
            return '';
        }

        $parts = explode(';', $clean, 2);
        return trim($parts[0]);
    }

    private function isAllowedMime(string $mime): bool
    {
        if ($mime === '') {
            return false;
        }

        return in_array($mime, self::ALLOWED_MIMES, true);
    }
}
