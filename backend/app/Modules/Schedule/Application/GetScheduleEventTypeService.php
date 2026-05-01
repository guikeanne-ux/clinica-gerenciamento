<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\NotFoundException;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEventType;

final class GetScheduleEventTypeService
{
    public function execute(string $uuid): ScheduleEventType
    {
        /** @var ScheduleEventType|null $eventType */
        $eventType = ScheduleEventType::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();

        if ($eventType === null) {
            throw new NotFoundException('Tipo de evento da agenda não encontrado.');
        }

        return $eventType;
    }
}
