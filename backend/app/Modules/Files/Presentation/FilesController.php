<?php

declare(strict_types=1);

namespace App\Modules\Files\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Files\Application\FileService;

final class FilesController
{
    public function __construct(private readonly FileService $fileService = new FileService())
    {
    }

    public function upload(Request $request): array
    {
        $user = $request->attribute('auth_user');
        $file = $this->fileService->upload($request->body, $user->uuid);

        return JsonResponse::make(ApiResponse::success('Upload realizado com sucesso.', [
            'uuid' => $file->uuid,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'extension' => $file->extension,
            'size_bytes' => $file->size_bytes,
            'checksum_hash' => $file->checksum_hash,
            'optimized' => $file->optimized,
            'classification' => $file->classification,
        ]), 201);
    }

    public function show(Request $request): array
    {
        $user = $request->attribute('auth_user');
        $file = $this->fileService->find((string) $request->attribute('uuid'), $user->uuid);

        return JsonResponse::make(ApiResponse::success(data: [
            'uuid' => $file->uuid,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'extension' => $file->extension,
            'size_bytes' => $file->size_bytes,
            'checksum_hash' => $file->checksum_hash,
            'classification' => $file->classification,
            'optimized' => $file->optimized,
            'related_module' => $file->related_module,
            'related_entity_type' => $file->related_entity_type,
            'related_entity_uuid' => $file->related_entity_uuid,
            'uploaded_by_user_uuid' => $file->uploaded_by_user_uuid,
        ]));
    }

    public function list(Request $request): array
    {
        $relatedModule = (string) ($request->query['related_module'] ?? 'company');
        $relatedEntityType = (string) ($request->query['related_entity_type'] ?? 'company');

        $items = $this->fileService->listByRelated($relatedModule, $relatedEntityType);

        return JsonResponse::make(ApiResponse::success(data: $items));
    }

    public function download(Request $request): array
    {
        $user = $request->attribute('auth_user');
        $file = $this->fileService->download((string) $request->attribute('uuid'), $user->uuid);

        return [
            'status' => 200,
            'body' => $file->content_blob,
            'headers' => [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
            ],
        ];
    }

    public function delete(Request $request): array
    {
        $user = $request->attribute('auth_user');
        $this->fileService->softDelete((string) $request->attribute('uuid'), $user->uuid);
        return JsonResponse::make(ApiResponse::success('Arquivo removido com sucesso.'));
    }
}
