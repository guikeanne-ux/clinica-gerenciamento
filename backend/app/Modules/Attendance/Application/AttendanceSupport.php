<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Application;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;
use Illuminate\Database\Capsule\Manager as DB;

final class AttendanceSupport
{
    public function __construct(private readonly PermissionService $permissions = new PermissionService())
    {
    }

    public function assertUuidExists(string $table, ?string $uuid, string $field, string $message): void
    {
        if ($uuid === null || trim($uuid) === '') {
            throw new ValidationException('Erro de validação.', [[
                'field' => $field,
                'message' => $message,
            ]]);
        }

        $exists = DB::table($table)->where('uuid', $uuid)->whereNull('deleted_at')->exists();
        if (! $exists) {
            throw new NotFoundException($message);
        }
    }

    public function findAttendance(string $uuid): Attendance
    {
        /** @var Attendance|null $attendance */
        $attendance = Attendance::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($attendance === null) {
            throw new NotFoundException('Atendimento não encontrado.');
        }

        return $attendance;
    }

    public function assertCanViewAttendance(User $user, Attendance $attendance): void
    {
        if (
            $this->permissions->has($user, 'attendance.update_all')
            || $this->permissions->has($user, 'attendance.view')
        ) {
            return;
        }

        if (
            $user->professional_uuid !== null
            && (string) $user->professional_uuid === (string) $attendance->professional_uuid
        ) {
            return;
        }

        throw new AuthorizationException('Acesso negado ao atendimento.');
    }

    public function assertCanMutateAttendance(User $user, Attendance $attendance): void
    {
        if ($attendance->status === 'finalizado') {
            throw new ConflictException('Atendimento finalizado é imutável.');
        }

        if ($this->permissions->has($user, 'attendance.update_all')) {
            return;
        }

        if (
            $this->permissions->has($user, 'attendance.update_own')
            && $user->professional_uuid !== null
            && (string) $user->professional_uuid === (string) $attendance->professional_uuid
        ) {
            return;
        }

        throw new AuthorizationException('Sem permissão para editar este atendimento.');
    }

    public function assertCanSetStatus(Attendance $attendance, string $nextStatus): void
    {
        $current = (string) $attendance->status;
        $terminalStatuses = ['finalizado', 'cancelado', 'falta'];

        if (in_array($current, $terminalStatuses, true) && $current !== $nextStatus) {
            throw new ConflictException('Status atual é terminal e não pode ser alterado.');
        }
    }

    public function findScheduleEvent(string $uuid): ScheduleEvent
    {
        /** @var ScheduleEvent|null $event */
        $event = ScheduleEvent::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($event === null) {
            throw new NotFoundException('Evento de agenda não encontrado.');
        }

        return $event;
    }

    public function attendanceCanStartFromEvent(ScheduleEvent $event): void
    {
        if (! (bool) $event->is_attendance) {
            throw new ConflictException('Este evento não permite iniciar atendimento.');
        }

        if (in_array((string) $event->status, ['cancelado', 'falta', 'bloqueado'], true)) {
            throw new ConflictException('Não é possível iniciar atendimento para este evento.');
        }
    }

    public function assertCanStartFromSchedule(
        User $user,
        ScheduleEvent $event,
        ?Attendance $existingAttendance = null
    ): void {
        if ($this->permissions->has($user, 'attendance.update_all')) {
            return;
        }

        $responsibleProfessionalUuid = $existingAttendance !== null
            ? (string) $existingAttendance->professional_uuid
            : (string) $event->professional_uuid;

        if (
            $user->professional_uuid !== null
            && (string) $user->professional_uuid !== ''
            && (string) $user->professional_uuid === $responsibleProfessionalUuid
        ) {
            return;
        }

        throw new AuthorizationException(
            'Somente o profissional responsável pelo atendimento pode iniciar este evento.'
        );
    }
}
