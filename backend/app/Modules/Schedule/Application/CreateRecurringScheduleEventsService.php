<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\ErrorCode;
use App\Core\Exceptions\ValidationException;
use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Schedule\Infrastructure\Models\ScheduleEvent;
use Illuminate\Database\Capsule\Manager as DB;

final class CreateRecurringScheduleEventsService
{
    public function __construct(
        private readonly CheckScheduleConflictService $conflictService = new CheckScheduleConflictService(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    /**
     * @param array<string, mixed> $basePayload
     */
    public function execute(
        array $basePayload,
        array $recurrence,
        string $actorUserUuid,
        bool $allowOverrideConflict
    ): array {
        $frequency = strtolower(trim((string) ($recurrence['frequency'] ?? '')));
        if ($frequency !== 'weekly') {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'recurrence.frequency',
                'message' => 'Apenas recorrência semanal é suportada nesta etapa.',
            ]]);
        }

        $until = trim((string) ($recurrence['until'] ?? ''));
        if ($until === '' || strtotime($until) === false) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'recurrence.until',
                'message' => 'Data final da recorrência inválida.',
            ]]);
        }

        $interval = max(1, (int) ($recurrence['interval'] ?? 1));
        $weekDays = $this->normalizeWeekDays($recurrence['week_days'] ?? []);

        $baseStartsAt = (string) $basePayload['starts_at'];
        $baseEndsAt = (string) $basePayload['ends_at'];
        $duration = strtotime($baseEndsAt) - strtotime($baseStartsAt);

        if ($duration <= 0) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'ends_at',
                'message' => 'Data/hora final deve ser posterior a data/hora inicial.',
            ]]);
        }

        $occurrences = $this->buildOccurrences($baseStartsAt, $duration, $until, $interval, $weekDays);

        if ($occurrences === []) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'recurrence',
                'message' => 'Nenhuma ocorrência foi gerada para a regra informada.',
            ]]);
        }

        if (count($occurrences) > ScheduleConstants::MAX_RECURRING_OCCURRENCES) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'recurrence',
                'message' => 'Limite máximo de ocorrências excedido para recorrência.',
            ]]);
        }

        $professionalUuid = isset($basePayload['professional_uuid']) ? (string) $basePayload['professional_uuid'] : '';
        $professionalUuid = trim($professionalUuid) === '' ? null : $professionalUuid;

        $allConflicts = [];
        if ($professionalUuid !== null) {
            foreach ($occurrences as $occurrence) {
                $conflicts = $this->conflictService->findConflicts(
                    $professionalUuid,
                    $occurrence['starts_at'],
                    $occurrence['ends_at']
                );

                if ($conflicts !== []) {
                    $allConflicts[] = [
                        'starts_at' => $occurrence['starts_at'],
                        'ends_at' => $occurrence['ends_at'],
                        'conflicts' => $conflicts,
                    ];
                }
            }
        }

        if ($allConflicts !== [] && ! $allowOverrideConflict) {
            throw new ConflictException(
                'Este profissional já possui compromisso nesse horário.',
                ErrorCode::SCHEDULE_CONFLICT,
                ['recurrence_conflicts' => $allConflicts]
            );
        }

        $groupUuid = Uuid::v4();
        $created = [];

        DB::connection()->transaction(function () use (
            &$created,
            $occurrences,
            $basePayload,
            $groupUuid,
            $actorUserUuid
        ): void {
            foreach ($occurrences as $occurrence) {
                $event = ScheduleEvent::query()->create([
                    'uuid' => Uuid::v4(),
                    'title' => (string) $basePayload['title'],
                    'description' => $basePayload['description'] ?? null,
                    'event_type_uuid' => (string) $basePayload['event_type_uuid'],
                    'is_attendance' => (bool) ($basePayload['is_attendance'] ?? false),
                    'patient_uuid' => $basePayload['patient_uuid'] ?? null,
                    'professional_uuid' => $basePayload['professional_uuid'] ?? null,
                    'starts_at' => $occurrence['starts_at'],
                    'ends_at' => $occurrence['ends_at'],
                    'all_day' => (bool) ($basePayload['all_day'] ?? false),
                    'status' => (string) $basePayload['status'],
                    'origin' => (string) $basePayload['origin'],
                    'recurrence_rule' => json_encode(
                        $basePayload['recurrence'],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'recurrence_group_uuid' => $groupUuid,
                    'room_or_location' => $basePayload['room_or_location'] ?? null,
                    'color_override' => $basePayload['color_override'] ?? null,
                    'created_by_user_uuid' => $actorUserUuid,
                    'updated_by_user_uuid' => $actorUserUuid,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $created[] = $event;
            }
        });

        foreach ($created as $event) {
            $this->audit->log('schedule.events.created', $actorUserUuid, ['event_uuid' => $event->uuid]);
        }

        $this->audit->log('schedule.events.recurring_created', $actorUserUuid, [
            'recurrence_group_uuid' => $groupUuid,
            'occurrences_count' => count($created),
        ]);

        if ($allowOverrideConflict && $allConflicts !== []) {
            $this->audit->log('schedule.events.conflict_overridden', $actorUserUuid, [
                'action' => 'create_recurrence',
                'professional_uuid' => $professionalUuid,
                'recurrence_group_uuid' => $groupUuid,
                'conflicts' => $allConflicts,
            ]);
        }

        return [
            'recurrence_group_uuid' => $groupUuid,
            'occurrences_count' => count($created),
            'items' => array_map(static fn (ScheduleEvent $event): array => $event->toArray(), $created),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeWeekDays(mixed $input): array
    {
        $days = [];

        if (is_array($input)) {
            foreach ($input as $item) {
                if (! is_string($item)) {
                    continue;
                }

                $day = strtoupper(trim($item));
                if (in_array($day, ScheduleConstants::RECURRENCE_WEEK_DAYS, true) && ! in_array($day, $days, true)) {
                    $days[] = $day;
                }
            }
        }

        return $days;
    }

    /**
     * @return array<int, array{starts_at: string, ends_at: string}>
     */
    private function buildOccurrences(
        string $baseStartsAt,
        int $durationSeconds,
        string $until,
        int $interval,
        array $weekDays
    ): array {
        $baseStartTs = strtotime($baseStartsAt);
        if ($baseStartTs === false) {
            return [];
        }

        $startDate = date('Y-m-d', $baseStartTs);
        $startTime = date('H:i:s', $baseStartTs);
        $untilDate = date('Y-m-d', (int) strtotime($until));

        if ($weekDays === []) {
            $weekDays = [date('D', $baseStartTs)];
            $weekDays = array_map(static fn (string $day): string => match ($day) {
                'Mon' => 'MO',
                'Tue' => 'TU',
                'Wed' => 'WE',
                'Thu' => 'TH',
                'Fri' => 'FR',
                'Sat' => 'SA',
                default => 'SU',
            }, $weekDays);
        }

        $occurrences = [];
        $cursor = $startDate;
        $baseMondayTs = strtotime(date('Y-m-d', strtotime('monday this week', $baseStartTs)));

        while ($cursor <= $untilDate) {
            $cursorTs = strtotime($cursor . ' ' . $startTime);
            if ($cursorTs === false) {
                break;
            }

            $cursorMondayTs = strtotime(date('Y-m-d', strtotime('monday this week', $cursorTs)));
            if ($cursorMondayTs === false || $baseMondayTs === false) {
                break;
            }

            $weeksDelta = (int) floor(($cursorMondayTs - $baseMondayTs) / 604800);
            $dayCode = match (date('N', $cursorTs)) {
                '1' => 'MO',
                '2' => 'TU',
                '3' => 'WE',
                '4' => 'TH',
                '5' => 'FR',
                '6' => 'SA',
                default => 'SU',
            };

            if ($weeksDelta >= 0 && $weeksDelta % $interval === 0 && in_array($dayCode, $weekDays, true)) {
                $start = date('Y-m-d H:i:s', $cursorTs);
                $end = date('Y-m-d H:i:s', $cursorTs + $durationSeconds);

                if ($start >= date('Y-m-d H:i:s', $baseStartTs)) {
                    $occurrences[] = ['starts_at' => $start, 'ends_at' => $end];
                }
            }

            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        return $occurrences;
    }
}
