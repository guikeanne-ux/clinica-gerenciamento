<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Application;

use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;

final class CancelAttendanceService
{
    public function __construct(
        private readonly AttendanceSupport $support = new AttendanceSupport(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    public function execute(string $attendanceUuid, User $authUser): Attendance
    {
        $attendance = $this->support->findAttendance($attendanceUuid);
        $this->support->assertCanMutateAttendance($authUser, $attendance);
        $this->support->assertCanSetStatus($attendance, 'cancelado');

        $attendance->status = 'cancelado';
        $attendance->updated_by_user_uuid = (string) $authUser->uuid;
        $attendance->updated_at = date('Y-m-d H:i:s');
        $attendance->save();

        $this->audit->log('attendance.canceled', (string) $authUser->uuid, ['attendance_uuid' => $attendance->uuid]);

        return $attendance;
    }
}
