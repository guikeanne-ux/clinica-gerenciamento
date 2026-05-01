<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\Attendance\Application\CancelAttendanceService;
use App\Modules\Attendance\Application\CreateAttendanceService;
use App\Modules\Attendance\Application\FinalizeAttendanceService;
use App\Modules\Attendance\Application\MarkAttendanceAsNoShowService;
use App\Modules\Attendance\Application\StartAttendanceFromScheduleService;
use App\Modules\Attendance\Application\SubstituteAttendanceProfessionalService;
use App\Modules\Attendance\Application\UpdateAttendanceService;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Person\Infrastructure\Models\Patient;
use App\Modules\Person\Infrastructure\Models\Professional;

final class AttendanceController
{
    private readonly SubstituteAttendanceProfessionalService $substituteService;

    public function __construct(
        private readonly CreateAttendanceService $createService = new CreateAttendanceService(),
        private readonly StartAttendanceFromScheduleService $startService = new StartAttendanceFromScheduleService(),
        private readonly UpdateAttendanceService $updateService = new UpdateAttendanceService(),
        private readonly FinalizeAttendanceService $finalizeService = new FinalizeAttendanceService(),
        private readonly CancelAttendanceService $cancelService = new CancelAttendanceService(),
        private readonly MarkAttendanceAsNoShowService $noShowService = new MarkAttendanceAsNoShowService(),
        ?SubstituteAttendanceProfessionalService $substituteService = null
    ) {
        $this->substituteService = $substituteService ?? new SubstituteAttendanceProfessionalService();
    }

    public function index(Request $request): array
    {
        /** @var array<int, Attendance> $collection */
        $collection = Attendance::query()->whereNull('deleted_at')->orderByDesc('created_at')->get()->all();
        $items = array_map(static fn (Attendance $item): array => $item->toArray(), $collection);

        return JsonResponse::make(ApiResponse::success(data: ['items' => $items]));
    }

    public function show(Request $request): array
    {
        /** @var Attendance|null $attendance */
        $attendance = Attendance::query()
            ->where('uuid', (string) $request->attribute('uuid'))
            ->whereNull('deleted_at')
            ->first();

        if ($attendance === null) {
            return JsonResponse::make(ApiResponse::error('Atendimento não encontrado.'), 404);
        }

        $data = $attendance->toArray();
        $data['patient_name'] = $this->resolvePatientName($attendance->patient_uuid);
        $data['professional_name'] = $this->resolveProfessionalName($attendance->professional_uuid);
        $data['original_professional_name'] = $this->resolveProfessionalName($attendance->original_professional_uuid);
        $data['substituted_professional_name'] = $this->resolveProfessionalName(
            $attendance->substituted_professional_uuid
        );

        return JsonResponse::make(ApiResponse::success(data: $data));
    }

    public function store(Request $request): array
    {
        $attendance = $this->createService->execute($request->body, $request->attribute('auth_user'));
        return JsonResponse::make(
            ApiResponse::success('Atendimento criado com sucesso.', $attendance->toArray()),
            201
        );
    }

    public function startFromSchedule(Request $request): array
    {
        $attendance = $this->startService->execute(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );
        return JsonResponse::make(
            ApiResponse::success('Atendimento iniciado com sucesso.', $attendance->toArray())
        );
    }

    public function update(Request $request): array
    {
        $attendance = $this->updateService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );

        return JsonResponse::make(ApiResponse::success('Atendimento atualizado com sucesso.', $attendance->toArray()));
    }

    public function finalize(Request $request): array
    {
        $attendance = $this->finalizeService->execute(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );
        return JsonResponse::make(
            ApiResponse::success('Atendimento finalizado com sucesso.', $attendance->toArray())
        );
    }

    public function cancel(Request $request): array
    {
        $attendance = $this->cancelService->execute(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );
        return JsonResponse::make(
            ApiResponse::success('Atendimento cancelado com sucesso.', $attendance->toArray())
        );
    }

    public function noShow(Request $request): array
    {
        $attendance = $this->noShowService->execute(
            (string) $request->attribute('uuid'),
            $request->attribute('auth_user')
        );
        return JsonResponse::make(
            ApiResponse::success('Falta registrada com sucesso.', $attendance->toArray())
        );
    }

    public function substituteProfessional(Request $request): array
    {
        $result = $this->substituteService->execute(
            (string) $request->attribute('uuid'),
            $request->body,
            $request->attribute('auth_user')
        );

        return JsonResponse::make(ApiResponse::success('Substituição registrada com sucesso.', $result));
    }

    private function resolvePatientName(?string $uuid): ?string
    {
        if ($uuid === null || $uuid === '') {
            return null;
        }

        /** @var Patient|null $patient */
        $patient = Patient::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        return $patient !== null ? (string) $patient->full_name : null;
    }

    private function resolveProfessionalName(?string $uuid): ?string
    {
        if ($uuid === null || $uuid === '') {
            return null;
        }

        /** @var Professional|null $professional */
        $professional = Professional::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        return $professional !== null ? (string) $professional->full_name : null;
    }
}
