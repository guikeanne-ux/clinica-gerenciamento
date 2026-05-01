<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Application;

use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;

final class StartAttendanceFromScheduleService
{
    public function __construct(
        private readonly AttendanceSupport $support = new AttendanceSupport(),
        private readonly CreateAttendanceService $createAttendance = new CreateAttendanceService(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $eventUuid, User $authUser): Attendance
    {
        $event = $this->support->findScheduleEvent($eventUuid);
        $this->support->attendanceCanStartFromEvent($event);

        /** @var Attendance|null $existing */
        $existing = Attendance::query()->where('schedule_event_uuid', $eventUuid)->whereNull('deleted_at')->first();
        if ($existing !== null) {
            $this->support->assertCanStartFromSchedule($authUser, $event, $existing);
            return $existing;
        }

        $this->support->assertCanStartFromSchedule($authUser, $event);

        $attendance = $this->createAttendance->execute([
            'patient_uuid' => $event->patient_uuid,
            'professional_uuid' => $event->professional_uuid,
            'schedule_event_uuid' => $eventUuid,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
            'status' => 'em_andamento',
            'attendance_type' => $event->attendance_kind ?? 'consulta',
        ], $authUser);

        $this->audit->log('attendance.started_from_schedule', (string) $authUser->uuid, [
            'attendance_uuid' => $attendance->uuid,
            'schedule_event_uuid' => $eventUuid,
        ]);

        return $attendance;
    }
}
