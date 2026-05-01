<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\ClinicalRecord\Application\CreateClinicalRecordComplementService;
use App\Modules\ClinicalRecord\Application\CreateClinicalRecordService;
use App\Modules\ClinicalRecord\Application\DeleteClinicalRecordService;
use App\Modules\ClinicalRecord\Application\FinalizeClinicalRecordService;
use App\Modules\ClinicalRecord\Application\UpdateClinicalRecordDraftService;
use App\Modules\ClinicalRecord\Infrastructure\Models\ClinicalRecord;

final class ClinicalRecordController
{
    private readonly CreateClinicalRecordComplementService $complementService;

    public function __construct(
        private readonly CreateClinicalRecordService $createService = new CreateClinicalRecordService(),
        private readonly UpdateClinicalRecordDraftService $updateService = new UpdateClinicalRecordDraftService(),
        private readonly FinalizeClinicalRecordService $finalizeService = new FinalizeClinicalRecordService(),
        private readonly DeleteClinicalRecordService $deleteService = new DeleteClinicalRecordService(),
        ?CreateClinicalRecordComplementService $complementService = null
    ) {
        $this->complementService = $complementService ?? new CreateClinicalRecordComplementService();
    }

    public function indexByAttendance(Request $request): array
    {
        /** @var array<int, ClinicalRecord> $records */
        $records = ClinicalRecord::query()
            ->where('attendance_uuid', (string) $request->attribute('uuid'))
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->all();
        $items = array_map(static fn (ClinicalRecord $record): array => $record->toArray(), $records);

        return JsonResponse::make(ApiResponse::success(data: ['items' => $items]));
    }

    public function store(Request $request): array
    {
        $record = $this->createService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );

        return JsonResponse::make(ApiResponse::success('Registro criado com sucesso.', $record->toArray()), 201);
    }

    public function show(Request $request): array
    {
        /** @var ClinicalRecord|null $record */
        $record = ClinicalRecord::query()
            ->where('uuid', (string) $request->attribute('uuid'))
            ->whereNull('deleted_at')
            ->first();
        if ($record === null) {
            return JsonResponse::make(ApiResponse::error('Registro clínico não encontrado.'), 404);
        }

        return JsonResponse::make(ApiResponse::success(data: $record->toArray()));
    }

    public function update(Request $request): array
    {
        $record = $this->updateService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );
        return JsonResponse::make(ApiResponse::success('Rascunho atualizado com sucesso.', $record->toArray()));
    }

    public function finalize(Request $request): array
    {
        $record = $this->finalizeService->execute(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );
        return JsonResponse::make(ApiResponse::success('Registro finalizado com sucesso.', $record->toArray()));
    }

    public function complement(Request $request): array
    {
        $record = $this->complementService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );
        return JsonResponse::make(ApiResponse::success('Complemento criado com sucesso.', $record->toArray()), 201);
    }

    public function delete(Request $request): array
    {
        $this->deleteService->execute(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );

        return JsonResponse::make(ApiResponse::success('Registro clínico excluído com sucesso.'));
    }
}
