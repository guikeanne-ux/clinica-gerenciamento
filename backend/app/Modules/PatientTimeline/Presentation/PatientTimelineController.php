<?php

declare(strict_types=1);

namespace App\Modules\PatientTimeline\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\PatientTimeline\Application\PatientTimelineService;

final class PatientTimelineController
{
    public function __construct(private readonly PatientTimelineService $service = new PatientTimelineService())
    {
    }

    public function index(Request $request): array
    {
        $timeline = $this->service->execute(
            (string) $request->attribute('uuid'),
            (string) $request->attribute('auth_user')->uuid
        );
        return JsonResponse::make(ApiResponse::success(data: $timeline));
    }
}
