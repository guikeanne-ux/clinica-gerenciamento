<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Application;

use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;

final class UpdateAttendanceService
{
    public function __construct(
        private readonly AttendanceSupport $support = new AttendanceSupport(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $attendanceUuid, array $data, User $authUser): Attendance
    {
        $attendance = $this->support->findAttendance($attendanceUuid);
        $this->support->assertCanMutateAttendance($authUser, $attendance);

        foreach (['starts_at', 'ends_at', 'status', 'attendance_type', 'modality', 'internal_notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $attendance->{$field} = $data[$field] === '' ? null : $data[$field];
            }
        }

        $attendance->duration_minutes = $this->duration(
            $attendance->starts_at !== null ? (string) $attendance->starts_at : null,
            $attendance->ends_at !== null ? (string) $attendance->ends_at : null
        );
        $attendance->updated_by_user_uuid = (string) $authUser->uuid;
        $attendance->updated_at = date('Y-m-d H:i:s');
        $attendance->save();

        $this->audit->log('attendance.updated', (string) $authUser->uuid, ['attendance_uuid' => $attendance->uuid]);

        return $attendance;
    }

    private function duration(?string $startsAt, ?string $endsAt): ?int
    {
        if ($startsAt === null || $endsAt === null) {
            return null;
        }

        return max(0, (int) round((strtotime($endsAt) - strtotime($startsAt)) / 60));
    }
}
