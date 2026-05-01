<?php

declare(strict_types=1);

namespace App\Modules\PatientTimeline\Application;

use App\Modules\Attendance\Infrastructure\Models\Attendance;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\ClinicalRecord\Infrastructure\Models\AudioRecord;
use App\Modules\ClinicalRecord\Infrastructure\Models\ClinicalRecord;

final class PatientTimelineService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function execute(string $patientUuid, string $actorUserUuid): array
    {
        /** @var array<int, Attendance> $attendances */
        $attendances = Attendance::query()->where('patient_uuid', $patientUuid)->whereNull('deleted_at')->get()->all();
        /** @var array<int, ClinicalRecord> $records */
        $records = ClinicalRecord::query()->where('patient_uuid', $patientUuid)->whereNull('deleted_at')->get()->all();
        /** @var array<int, AudioRecord> $audios */
        $audios = AudioRecord::query()->where('patient_uuid', $patientUuid)->whereNull('deleted_at')->get()->all();

        /** @var array<int, array<string, mixed>> $items */
        $items = [];

        foreach ($attendances as $attendance) {
            $items[] = [
                'type' => 'attendance',
                'uuid' => $attendance->uuid,
                'status' => $attendance->status,
                'patient_uuid' => $attendance->patient_uuid,
                'professional_uuid' => $attendance->professional_uuid,
                'happened_at' => $attendance->starts_at ?? $attendance->created_at,
                'data' => $attendance->toArray(),
            ];
        }

        foreach ($records as $record) {
            $items[] = [
                'type' => 'clinical_record',
                'uuid' => $record->uuid,
                'status' => $record->status,
                'patient_uuid' => $record->patient_uuid,
                'professional_uuid' => $record->professional_uuid,
                'happened_at' => $record->created_at,
                'data' => $record->toArray(),
            ];
        }

        foreach ($audios as $audio) {
            $items[] = [
                'type' => 'audio_record',
                'uuid' => $audio->uuid,
                'status' => $audio->status,
                'patient_uuid' => $audio->patient_uuid,
                'professional_uuid' => $audio->professional_uuid,
                'happened_at' => $audio->created_at,
                'data' => $audio->toArray(),
            ];
        }

        usort(
            $items,
            static fn (array $a, array $b): int => strcmp((string) $b['happened_at'], (string) $a['happened_at'])
        );

        $this->audit->log('patient.timeline.viewed', $actorUserUuid, ['patient_uuid' => $patientUuid]);

        return ['items' => $items];
    }
}
