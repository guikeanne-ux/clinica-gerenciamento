<?php

declare(strict_types=1);

use App\Core\Database\DatabaseManager;
use Tests\Support\ApiRequester;
use Tests\Support\ScheduleApi;

beforeEach(function (): void {
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = ':memory:';
    $_ENV['JWT_SECRET'] = 'test-secret';
    $_ENV['JWT_TTL'] = '3600';

    DatabaseManager::reset();
    require dirname(__DIR__, 3) . '/bootstrap.php';
    require dirname(__DIR__, 3) . '/database/migrations/run.php';
    require dirname(__DIR__, 3) . '/database/seeders/run.php';
});

it('inicia atendimento pela agenda e não duplica no mesmo evento', function (): void {
    $token = ScheduleApi::tokenFor();
    $type = ScheduleApi::createEventType($token, ['can_generate_attendance' => true]);
    $patient = ScheduleApi::createPatient($token);
    $professional = ScheduleApi::createProfessional($token);

    $event = ScheduleApi::createEvent($token, [
        'title' => 'Atendimento',
        'event_type_uuid' => $type,
        'patient_uuid' => $patient,
        'professional_uuid' => $professional,
        'starts_at' => '2026-05-06 09:00:00',
        'ends_at' => '2026-05-06 09:50:00',
        'is_attendance' => true,
    ]);

    $eventUuid = $event['data']['uuid'];

    $start1 = ApiRequester::call('POST', '/api/v1/schedule-events/' . $eventUuid . '/start-attendance', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $start2 = ApiRequester::call('POST', '/api/v1/schedule-events/' . $eventUuid . '/start-attendance', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($start1['status'])->toBe(200);
    expect($start2['status'])->toBe(200);

    $a1 = json_decode($start1['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    $a2 = json_decode($start2['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    expect($a1)->toBe($a2);
});

it('bloqueia iniciar atendimento para evento comum', function (): void {
    $token = ScheduleApi::tokenFor();
    $type = ScheduleApi::createEventType($token, ['can_generate_attendance' => false]);

    $event = ScheduleApi::createEvent($token, [
        'title' => 'Reunião',
        'event_type_uuid' => $type,
        'starts_at' => '2026-05-06 10:00:00',
        'ends_at' => '2026-05-06 11:00:00',
        'is_attendance' => false,
    ]);

    $res = ApiRequester::call('POST', '/api/v1/schedule-events/' . $event['data']['uuid'] . '/start-attendance', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(409);
});

it('permite iniciar atendimento somente para profissional responsável ou admin', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $type = ScheduleApi::createEventType($adminToken, ['can_generate_attendance' => true]);
    $patient = ScheduleApi::createPatient($adminToken);
    $professionalA = ScheduleApi::createProfessional($adminToken, 'Profissional Responsável');
    $professionalB = ScheduleApi::createProfessional($adminToken, 'Profissional Não Responsável');

    $event = ScheduleApi::createEvent($adminToken, [
        'title' => 'Atendimento restrito por profissional',
        'event_type_uuid' => $type,
        'patient_uuid' => $patient,
        'professional_uuid' => $professionalA,
        'starts_at' => '2026-05-06 13:00:00',
        'ends_at' => '2026-05-06 13:50:00',
        'is_attendance' => true,
    ]);

    $tokenResponsible = ScheduleApi::createLimitedUser('prof-responsavel', ['attendance.start_from_schedule']);
    $tokenNotResponsible = ScheduleApi::createLimitedUser('prof-nao-responsavel', ['attendance.start_from_schedule']);
    ScheduleApi::attachProfessionalToUser('prof-responsavel', $professionalA);
    ScheduleApi::attachProfessionalToUser('prof-nao-responsavel', $professionalB);

    $eventUuid = $event['data']['uuid'];
    $startResponsible = ApiRequester::call('POST', '/api/v1/schedule-events/' . $eventUuid . '/start-attendance', [], [
        'Authorization' => 'Bearer ' . $tokenResponsible,
    ]);
    $startNotResponsible = ApiRequester::call('POST', '/api/v1/schedule-events/' . $eventUuid . '/start-attendance', [], [
        'Authorization' => 'Bearer ' . $tokenNotResponsible,
    ]);
    $startAdmin = ApiRequester::call('POST', '/api/v1/schedule-events/' . $eventUuid . '/start-attendance', [], [
        'Authorization' => 'Bearer ' . $adminToken,
    ]);

    expect($startResponsible['status'])->toBe(200);
    expect($startNotResponsible['status'])->toBe(403);
    expect($startAdmin['status'])->toBe(200);
});

it('finaliza atendimento e impede atualização', function (): void {
    $token = ScheduleApi::tokenFor();
    $patient = ScheduleApi::createPatient($token);
    $professional = ScheduleApi::createProfessional($token);

    $created = ApiRequester::call('POST', '/api/v1/attendances', [
        'patient_uuid' => $patient,
        'professional_uuid' => $professional,
        'starts_at' => '2026-05-06 14:00:00',
        'ends_at' => '2026-05-06 14:45:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($created['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $finalize = ApiRequester::call('POST', '/api/v1/attendances/' . $uuid . '/finalize', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $show = ApiRequester::call('GET', '/api/v1/attendances/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $update = ApiRequester::call('PUT', '/api/v1/attendances/' . $uuid, ['internal_notes' => 'teste'], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $showData = json_decode($show['body'], true, flags: JSON_THROW_ON_ERROR)['data'];
    expect($finalize['status'])->toBe(200);
    expect($showData['original_professional_uuid'])->toBe($professional);
    expect($update['status'])->toBe(409);
});

it('cria, finaliza e complementa registro clínico', function (): void {
    $token = ScheduleApi::tokenFor();
    $patient = ScheduleApi::createPatient($token);
    $professional = ScheduleApi::createProfessional($token);

    $attendance = ApiRequester::call('POST', '/api/v1/attendances', [
        'patient_uuid' => $patient,
        'professional_uuid' => $professional,
    ], ['Authorization' => 'Bearer ' . $token]);
    $attendanceUuid = json_decode($attendance['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $record = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/clinical-records', [
        'record_type' => 'evolucao',
        'title' => 'Evolução sessão 1',
        'content_markdown' => 'Paciente colaborou bem.',
    ], ['Authorization' => 'Bearer ' . $token]);

    $recordUuid = json_decode($record['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $finalize = ApiRequester::call('POST', '/api/v1/clinical-records/' . $recordUuid . '/finalize', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $updateAfter = ApiRequester::call('PUT', '/api/v1/clinical-records/' . $recordUuid, ['content_markdown' => 'x'], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $complement = ApiRequester::call('POST', '/api/v1/clinical-records/' . $recordUuid . '/complement', [
        'content_markdown' => 'Complemento clínico posterior.',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($record['status'])->toBe(201);
    expect($finalize['status'])->toBe(200);
    expect($updateAfter['status'])->toBe(409);
    expect($complement['status'])->toBe(201);
});

it('anexa áudio e consulta timeline do paciente', function (): void {
    $token = ScheduleApi::tokenFor();
    $patient = ScheduleApi::createPatient($token);
    $professional = ScheduleApi::createProfessional($token);

    $attendance = ApiRequester::call('POST', '/api/v1/attendances', [
        'patient_uuid' => $patient,
        'professional_uuid' => $professional,
    ], ['Authorization' => 'Bearer ' . $token]);
    $attendanceUuid = json_decode($attendance['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $upload = ApiRequester::call('POST', '/api/v1/files/upload', [
        'original_name' => 'audio.wav',
        'mime_type' => 'audio/wav',
        'content_base64' => base64_encode('RIFF....WAVEfmt '),
        'classification' => 'documento_clinico',
        'related_module' => 'clinical_record',
        'related_entity_type' => 'attendance',
        'related_entity_uuid' => $attendanceUuid,
    ], ['Authorization' => 'Bearer ' . $token]);

    $fileUuid = json_decode($upload['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $audio = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/audio-records', [
        'title' => 'Áudio sessão',
        'file_uuid' => $fileUuid,
        'duration_seconds' => 30,
    ], ['Authorization' => 'Bearer ' . $token]);

    $timeline = ApiRequester::call('GET', '/api/v1/patients/' . $patient . '/timeline', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($audio['status'])->toBe(201);
    expect($timeline['status'])->toBe(200);
});

it('registra substituição de profissional com atualização do evento da agenda', function (): void {
    $token = ScheduleApi::tokenFor();
    $type = ScheduleApi::createEventType($token, ['can_generate_attendance' => true]);
    $patient = ScheduleApi::createPatient($token);
    $professionalA = ScheduleApi::createProfessional($token, 'Profissional Titular');
    $professionalB = ScheduleApi::createProfessional($token, 'Profissional Substituto');

    $event = ScheduleApi::createEvent($token, [
        'title' => 'Atendimento com possível substituição',
        'event_type_uuid' => $type,
        'patient_uuid' => $patient,
        'professional_uuid' => $professionalA,
        'starts_at' => '2026-05-07 10:00:00',
        'ends_at' => '2026-05-07 10:50:00',
        'is_attendance' => true,
    ]);

    $attendanceStart = ApiRequester::call(
        'POST',
        '/api/v1/schedule-events/' . $event['data']['uuid'] . '/start-attendance',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );
    $attendanceUuid = json_decode($attendanceStart['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $substitute = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/substitute-professional', [
        'professional_uuid' => $professionalB,
        'reason' => 'Cobertura por ausência do titular.',
        'sync_schedule_event_professional' => true,
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($substitute['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($substitute['status'])->toBe(200);
    expect($payload['data']['attendance']['professional_uuid'])->toBe($professionalB);
    expect($payload['data']['attendance']['original_professional_uuid'])->toBe($professionalA);

    $eventAfter = ApiRequester::call('GET', '/api/v1/schedule/events/' . $event['data']['uuid'], [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $eventData = json_decode($eventAfter['body'], true, flags: JSON_THROW_ON_ERROR)['data'];
    expect($eventData['professional_uuid'])->toBe($professionalB);
});

it('bloqueia substituição quando atendimento está finalizado', function (): void {
    $token = ScheduleApi::tokenFor();
    $patient = ScheduleApi::createPatient($token);
    $professionalA = ScheduleApi::createProfessional($token, 'Profissional A');
    $professionalB = ScheduleApi::createProfessional($token, 'Profissional B');

    $attendance = ApiRequester::call('POST', '/api/v1/attendances', [
        'patient_uuid' => $patient,
        'professional_uuid' => $professionalA,
    ], ['Authorization' => 'Bearer ' . $token]);
    $attendanceUuid = json_decode($attendance['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/finalize', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $substitute = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/substitute-professional', [
        'professional_uuid' => $professionalB,
        'reason' => 'Tentativa após finalizar',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($substitute['status'])->toBe(409);
});

it('não permite finalizar ou marcar falta após cancelamento', function (): void {
    $token = ScheduleApi::tokenFor();
    $patient = ScheduleApi::createPatient($token);
    $professional = ScheduleApi::createProfessional($token);

    $attendance = ApiRequester::call('POST', '/api/v1/attendances', [
        'patient_uuid' => $patient,
        'professional_uuid' => $professional,
    ], ['Authorization' => 'Bearer ' . $token]);
    $attendanceUuid = json_decode($attendance['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $cancel = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/cancel', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $finalizeAfter = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/finalize', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $noShowAfter = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/no-show', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($cancel['status'])->toBe(200);
    expect($finalizeAfter['status'])->toBe(409);
    expect($noShowAfter['status'])->toBe(409);
});

it('lista e remove áudio no atendimento', function (): void {
    $token = ScheduleApi::tokenFor();
    $patient = ScheduleApi::createPatient($token);
    $professional = ScheduleApi::createProfessional($token);

    $attendance = ApiRequester::call('POST', '/api/v1/attendances', [
        'patient_uuid' => $patient,
        'professional_uuid' => $professional,
    ], ['Authorization' => 'Bearer ' . $token]);
    $attendanceUuid = json_decode($attendance['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $upload = ApiRequester::call('POST', '/api/v1/files/upload', [
        'original_name' => 'audio.webm',
        'mime_type' => 'audio/webm',
        'content_base64' => base64_encode(str_repeat('a', 2048)),
        'classification' => 'documento_clinico',
        'related_module' => 'clinical_record',
        'related_entity_type' => 'attendance',
        'related_entity_uuid' => $attendanceUuid,
    ], ['Authorization' => 'Bearer ' . $token]);
    $fileUuid = json_decode($upload['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $created = ApiRequester::call('POST', '/api/v1/attendances/' . $attendanceUuid . '/audio-records', [
        'title' => 'Teste áudio',
        'file_uuid' => $fileUuid,
    ], ['Authorization' => 'Bearer ' . $token]);
    $audioUuid = json_decode($created['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $list = ApiRequester::call('GET', '/api/v1/attendances/' . $attendanceUuid . '/audio-records', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    $delete = ApiRequester::call('DELETE', '/api/v1/audio-records/' . $audioUuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($list['status'])->toBe(200);
    expect($delete['status'])->toBe(200);
});
