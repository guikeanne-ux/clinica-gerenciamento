<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Application;

use App\Core\Exceptions\ConflictException;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;

final class FinalizeAttendanceService
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
        $this->support->assertCanSetStatus($attendance, 'finalizado');

        if ($attendance->status === 'finalizado') {
            throw new ConflictException('Atendimento já está finalizado.');
        }

        $attendance->status = 'finalizado';
        $attendance->finalized_at = date('Y-m-d H:i:s');
        $attendance->finalized_by_user_uuid = (string) $authUser->uuid;
        $attendance->updated_by_user_uuid = (string) $authUser->uuid;
        $attendance->updated_at = date('Y-m-d H:i:s');
        $attendance->save();

        $this->audit->log('attendance.finalized', (string) $authUser->uuid, ['attendance_uuid' => $attendance->uuid]);

        return $attendance;
    }
}
