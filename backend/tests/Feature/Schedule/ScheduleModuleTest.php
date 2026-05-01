<?php

declare(strict_types=1);

use App\Core\Database\DatabaseManager;
use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;
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

it('cria tipo de evento válido', function (): void {
    $token = ScheduleApi::tokenFor();

    $res = ApiRequester::call('POST', '/api/v1/schedule/event-types', [
        'name' => 'Reunião Técnica',
        'category' => 'reuniao',
        'color' => '#11AA77',
        'requires_patient' => false,
        'requires_professional' => false,
        'status' => 'ativo',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('bloqueia tipo sem nome', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/schedule/event-types', [
        'category' => 'reuniao',
    ], ['Authorization' => 'Bearer ' . ScheduleApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('bloqueia cor inválida em tipo de evento', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/schedule/event-types', [
        'name' => 'Tipo inválido',
        'category' => 'outro',
        'color' => 'azul',
    ], ['Authorization' => 'Bearer ' . ScheduleApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('edita tipo de evento', function (): void {
    $token = ScheduleApi::tokenFor();
    $uuid = ScheduleApi::createEventType($token, ['name' => 'Tipo Antigo']);

    $res = ApiRequester::call('PUT', '/api/v1/schedule/event-types/' . $uuid, [
        'name' => 'Tipo Novo',
        'category' => 'evento_interno',
        'color' => '#334455',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
    expect(DB::table('schedule_event_types')->where('uuid', $uuid)->value('name'))->toBe('Tipo Novo');
});

it('faz soft delete de tipo de evento', function (): void {
    $token = ScheduleApi::tokenFor();
    $uuid = ScheduleApi::createEventType($token);

    $res = ApiRequester::call('DELETE', '/api/v1/schedule/event-types/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
    expect(DB::table('schedule_event_types')->where('uuid', $uuid)->value('deleted_at'))->not->toBeNull();
});

it('bloqueia acesso a tipos sem permissão', function (): void {
    $userUuid = Uuid::v4();
    $financeRoleUuid = (string) DB::table('roles')->where('name', 'Financeiro')->value('uuid');

    DB::table('users')->insert([
        'uuid' => $userUuid,
        'name' => 'Sem Agenda',
        'login' => 'semagenda_tipo',
        'email' => 'semagenda_tipo@clinica.local',
        'password_hash' => password_hash('123456', PASSWORD_ARGON2ID),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    DB::table('user_roles')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'role_uuid' => $financeRoleUuid,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $token = ScheduleApi::tokenFor('semagenda_tipo', '123456');

    $res = ApiRequester::call('GET', '/api/v1/schedule/event-types', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});

it('cria evento comum sem paciente quando tipo não exige paciente', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token, [
        'name' => 'Bloqueio Sala',
        'category' => 'bloqueio',
        'requires_patient' => false,
        'requires_professional' => false,
    ]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Manutenção da sala',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-05 09:00:00',
        'ends_at' => '2026-05-05 10:00:00',
        'status' => 'agendado',
        'origin' => 'manual',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('cria evento comum sem informar tipo e aplica tipo padrão automático', function (): void {
    $token = ScheduleApi::tokenFor();

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento comum sem tipo',
        'starts_at' => '2026-05-05 13:00:00',
        'ends_at' => '2026-05-05 14:00:00',
        'is_attendance' => false,
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(201);
    expect($payload['data']['event_type_uuid'])->not->toBeEmpty();
    expect($payload['data']['is_attendance'])->toBeFalse();
});

it('bloqueia evento sem paciente quando tipo exige paciente', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token, [
        'requires_patient' => true,
        'requires_professional' => false,
    ]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Atendimento sem paciente',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-05 09:00:00',
        'ends_at' => '2026-05-05 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('bloqueia evento sem profissional quando tipo exige profissional', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token, [
        'requires_patient' => false,
        'requires_professional' => true,
    ]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Atendimento sem profissional',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-05 09:00:00',
        'ends_at' => '2026-05-05 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('bloqueia evento com ends_at anterior a starts_at', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Horário inválido',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-05 11:00:00',
        'ends_at' => '2026-05-05 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('cria evento válido com paciente e profissional', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token, [
        'requires_patient' => true,
        'requires_professional' => true,
    ]);
    $patientUuid = ScheduleApi::createPatient($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Atendimento completo',
        'event_type_uuid' => $eventTypeUuid,
        'patient_uuid' => $patientUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-05-05 11:00:00',
        'ends_at' => '2026-05-05 12:00:00',
        'status' => 'confirmado',
        'origin' => 'manual',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('bloqueia atendimento agendado sem paciente', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Atendimento sem paciente',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-05-06 11:00:00',
        'ends_at' => '2026-05-06 12:00:00',
        'is_attendance' => true,
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('bloqueia atendimento agendado sem profissional', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $patientUuid = ScheduleApi::createPatient($token);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Atendimento sem profissional',
        'event_type_uuid' => $eventTypeUuid,
        'patient_uuid' => $patientUuid,
        'starts_at' => '2026-05-06 13:00:00',
        'ends_at' => '2026-05-06 14:00:00',
        'is_attendance' => true,
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('lista eventos por período', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Maio',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-10 09:00:00',
        'ends_at' => '2026-05-10 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Junho',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-06-10 09:00:00',
        'ends_at' => '2026-06-10 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('GET', '/api/v1/schedule/events?start_date=2026-05-01&end_date=2026-05-31', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect($json['data']['pagination']['total'])->toBe(1);
});

it('filtra eventos por profissional', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalA = ScheduleApi::createProfessional($token, 'Profissional A');
    $professionalB = ScheduleApi::createProfessional($token, 'Profissional B');

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Prof A',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalA,
        'starts_at' => '2026-05-11 09:00:00',
        'ends_at' => '2026-05-11 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Prof B',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalB,
        'starts_at' => '2026-05-11 11:00:00',
        'ends_at' => '2026-05-11 12:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('GET', '/api/v1/schedule/events?professional_uuid=' . $professionalA, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect($json['data']['pagination']['total'])->toBe(1);
});

it('filtra eventos por paciente', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $patientA = ScheduleApi::createPatient($token, 'Paciente A');
    $patientB = ScheduleApi::createPatient($token, 'Paciente B');

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Paciente A',
        'event_type_uuid' => $eventTypeUuid,
        'patient_uuid' => $patientA,
        'starts_at' => '2026-05-12 09:00:00',
        'ends_at' => '2026-05-12 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Paciente B',
        'event_type_uuid' => $eventTypeUuid,
        'patient_uuid' => $patientB,
        'starts_at' => '2026-05-12 11:00:00',
        'ends_at' => '2026-05-12 12:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('GET', '/api/v1/schedule/events?patient_uuid=' . $patientA, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect($json['data']['pagination']['total'])->toBe(1);
});

it('filtra eventos por status', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Confirmado',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-13 09:00:00',
        'ends_at' => '2026-05-13 10:00:00',
        'status' => 'confirmado',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Agendado',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-13 11:00:00',
        'ends_at' => '2026-05-13 12:00:00',
        'status' => 'agendado',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('GET', '/api/v1/schedule/events?status=confirmado', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect($json['data']['pagination']['total'])->toBe(1);
});

it('resolve cor priorizando profissional acima de tipo', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token, ['color' => '#112233']);
    $professionalUuid = ScheduleApi::createProfessional($token, 'Prof Cor');

    DB::table('professionals')->where('uuid', $professionalUuid)->update([
        'schedule_color' => '#44AA55',
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento com cor de profissional',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-05-20 10:00:00',
        'ends_at' => '2026-05-20 11:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('GET', '/api/v1/schedule/events?start_date=2026-05-20&end_date=2026-05-20', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['items'][0]['resolved_color'])->toBe('#44AA55');
    expect($payload['data']['items'][0]['resolved_color_source'])->toBe('professional');
});

it('resolve cor por tipo quando não há profissional', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token, ['color' => '#A142F4']);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento por tipo',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-21 10:00:00',
        'ends_at' => '2026-05-21 11:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('GET', '/api/v1/schedule/events?start_date=2026-05-21&end_date=2026-05-21', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['items'][0]['resolved_color'])->toBe('#A142F4');
    expect($payload['data']['items'][0]['resolved_color_source'])->toBe('event_type');
});

it('resolve cor padrão quando não há override profissional nem tipo', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token, ['color' => null]);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento cor padrão',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-22 10:00:00',
        'ends_at' => '2026-05-22 11:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('GET', '/api/v1/schedule/events?start_date=2026-05-22&end_date=2026-05-22', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['items'][0]['resolved_color'])->toBe('#157470');
    expect($payload['data']['items'][0]['resolved_color_source'])->toBe('default');
});

it('busca evento por uuid', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Busca UUID',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-14 09:00:00',
        'ends_at' => '2026-05-14 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('GET', '/api/v1/schedule/events/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
});

it('edita evento', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Antigo',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-14 11:00:00',
        'ends_at' => '2026-05-14 12:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('PUT', '/api/v1/schedule/events/' . $uuid, [
        'title' => 'Evento Atualizado',
        'status' => 'confirmado',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
    expect(DB::table('schedule_events')->where('uuid', $uuid)->value('title'))->toBe('Evento Atualizado');
});

it('faz soft delete de evento', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Delete',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-15 09:00:00',
        'ends_at' => '2026-05-15 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('DELETE', '/api/v1/schedule/events/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
    expect(DB::table('schedule_events')->where('uuid', $uuid)->value('deleted_at'))->not->toBeNull();
});

it('bloqueia acesso a eventos sem permissão', function (): void {
    $userUuid = Uuid::v4();
    $financeRoleUuid = (string) DB::table('roles')->where('name', 'Financeiro')->value('uuid');

    DB::table('users')->insert([
        'uuid' => $userUuid,
        'name' => 'Sem Agenda Eventos',
        'login' => 'semagenda_evento',
        'email' => 'semagenda_evento@clinica.local',
        'password_hash' => password_hash('123456', PASSWORD_ARGON2ID),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    DB::table('user_roles')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'role_uuid' => $financeRoleUuid,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $token = ScheduleApi::tokenFor('semagenda_evento', '123456');

    $res = ApiRequester::call('GET', '/api/v1/schedule/events', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});

it('bloqueia criação de evento sem permissão schedule.create', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);

    $token = ScheduleApi::createLimitedUser('semcreateschedule', ['schedule.view']);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Não deveria criar',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-20 09:00:00',
        'ends_at' => '2026-05-20 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(403);
});

it('bloqueia color override sem permissão avançada', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);
    $token = ScheduleApi::createLimitedUser('semcoloroverride', ['schedule.create']);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Cor manual sem permissão',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-21 14:00:00',
        'ends_at' => '2026-05-21 15:00:00',
        'color_override' => '#AABBCC',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('bloqueia edição de evento sem permissão schedule.update', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);
    $creatorToken = ScheduleApi::createLimitedUser('eventcreatoronly', ['schedule.create', 'schedule.view']);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento para bloquear edição',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-21 09:00:00',
        'ends_at' => '2026-05-21 10:00:00',
    ], ['Authorization' => 'Bearer ' . $creatorToken]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $noUpdateToken = ScheduleApi::createLimitedUser('semupdateschedule', ['schedule.view']);

    $res = ApiRequester::call('PUT', '/api/v1/schedule/events/' . $uuid, [
        'title' => 'Tentativa sem update',
    ], ['Authorization' => 'Bearer ' . $noUpdateToken]);

    expect($res['status'])->toBe(403);
});

it('bloqueia cancelamento sem permissão schedule.cancel', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);
    $creatorToken = ScheduleApi::createLimitedUser('eventcreatorcancel', ['schedule.create', 'schedule.view']);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento para bloquear cancelamento',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-22 09:00:00',
        'ends_at' => '2026-05-22 10:00:00',
    ], ['Authorization' => 'Bearer ' . $creatorToken]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $noCancelToken = ScheduleApi::createLimitedUser('semcancelschedule', ['schedule.view']);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/cancel', [
        'cancel_reason' => 'Tentativa sem permissão',
    ], ['Authorization' => 'Bearer ' . $noCancelToken]);

    expect($res['status'])->toBe(403);
});

it('permite listagem e detalhe com schedule.view mesmo sem schedule.view_all', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento privado do admin',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-23 09:00:00',
        'ends_at' => '2026-05-23 10:00:00',
    ], ['Authorization' => 'Bearer ' . $adminToken]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $userUuid = Uuid::v4();
    $financeRoleUuid = (string) DB::table('roles')->where('name', 'Financeiro')->value('uuid');
    $scheduleViewPermissionUuid = (string) DB::table('permissions')->where('code', 'schedule.view')->value('uuid');

    DB::table('users')->insert([
        'uuid' => $userUuid,
        'name' => 'Viewer sem view_all',
        'login' => 'viewonlyschedule',
        'email' => 'viewonlyschedule@clinica.local',
        'password_hash' => password_hash('123456', PASSWORD_ARGON2ID),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    DB::table('user_roles')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'role_uuid' => $financeRoleUuid,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    DB::table('user_permission_overrides')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'permission_uuid' => $scheduleViewPermissionUuid,
        'is_allowed' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $viewerToken = ScheduleApi::tokenFor('viewonlyschedule', '123456');

    $listRes = ApiRequester::call('GET', '/api/v1/schedule/events', [], [
        'Authorization' => 'Bearer ' . $viewerToken,
    ]);
    $listPayload = json_decode($listRes['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($listRes['status'])->toBe(200);
    expect($listPayload['data']['pagination']['total'])->toBe(1);

    $showRes = ApiRequester::call('GET', '/api/v1/schedule/events/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $viewerToken,
    ]);

    expect($showRes['status'])->toBe(200);
});

it('auditoria registra criação edição e exclusão de evento', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento Auditoria',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-05-16 09:00:00',
        'ends_at' => '2026-05-16 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('PUT', '/api/v1/schedule/events/' . $uuid, [
        'title' => 'Evento Auditoria Atualizado',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('DELETE', '/api/v1/schedule/events/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $events = DB::table('audit_logs')->pluck('event')->all();

    expect($events)->toContain('schedule.events.created');
    expect($events)->toContain('schedule.events.updated');
    expect($events)->toContain('schedule.events.deleted');
});

it('auditoria registra criação edição e exclusão de tipo de evento', function (): void {
    $token = ScheduleApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/schedule/event-types', [
        'name' => 'Tipo Auditoria',
        'category' => 'lembrete',
        'color' => '#556677',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('PUT', '/api/v1/schedule/event-types/' . $uuid, [
        'name' => 'Tipo Auditoria Atualizado',
        'category' => 'lembrete',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('DELETE', '/api/v1/schedule/event-types/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $events = DB::table('audit_logs')->pluck('event')->all();

    expect($events)->toContain('schedule.event_types.created');
    expect($events)->toContain('schedule.event_types.updated');
    expect($events)->toContain('schedule.event_types.deleted');
});

it('bloqueia conflito de horário do mesmo profissional', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);
    $professionalUuid = ScheduleApi::createProfessional($adminToken);
    $token = ScheduleApi::createLimitedUser('noconflictoverride', ['schedule.create']);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Primeiro compromisso',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-01 09:00:00',
        'ends_at' => '2026-08-01 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Conflitante',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-01 09:30:00',
        'ends_at' => '2026-08-01 10:30:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(409);
    expect($payload['meta']['error_code'])->toBe('SCHEDULE_CONFLICT');
});

it('permite horários encostados sem sobreposição', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Compromisso A',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-02 09:00:00',
        'ends_at' => '2026-08-02 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Compromisso B',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-02 10:00:00',
        'ends_at' => '2026-08-02 11:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('permite conflito quando usuário tem schedule override conflict', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);
    $professionalUuid = ScheduleApi::createProfessional($adminToken);

    $limitedToken = ScheduleApi::createLimitedUser(
        'overrideschedule',
        ['schedule.create', 'schedule.override_conflict']
    );

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Base',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-03 09:00:00',
        'ends_at' => '2026-08-03 10:00:00',
    ], ['Authorization' => 'Bearer ' . $limitedToken]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Sobreposto com override',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-03 09:15:00',
        'ends_at' => '2026-08-03 10:15:00',
    ], ['Authorization' => 'Bearer ' . $limitedToken]);

    expect($res['status'])->toBe(201);
    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('schedule.events.conflict_overridden');
});

it('ignora evento cancelado no conflito', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Será cancelado',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-04 09:00:00',
        'ends_at' => '2026-08-04 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/cancel', [
        'cancel_reason' => 'Paciente desmarcou',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Novo no mesmo horário',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-04 09:30:00',
        'ends_at' => '2026-08-04 10:30:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('ignora evento soft deleted no conflito', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Será removido',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-05 09:00:00',
        'ends_at' => '2026-08-05 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    ApiRequester::call('DELETE', '/api/v1/schedule/events/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Novo após soft delete',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-05 09:30:00',
        'ends_at' => '2026-08-05 10:30:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('bloqueia criação em horário bloqueado', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $blockedTypeUuid = ScheduleApi::createEventType($adminToken, [
        'name' => 'Bloqueio Profissional',
        'category' => 'bloqueio',
    ]);
    $eventTypeUuid = ScheduleApi::createEventType($adminToken, ['name' => 'Atendimento livre']);
    $professionalUuid = ScheduleApi::createProfessional($adminToken);
    $token = ScheduleApi::createLimitedUser('blockedconflict', ['schedule.create']);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Bloqueio',
        'event_type_uuid' => $blockedTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-06 09:00:00',
        'ends_at' => '2026-08-06 10:00:00',
        'status' => 'bloqueado',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Tentativa durante bloqueio',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-06 09:15:00',
        'ends_at' => '2026-08-06 09:45:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(409);
});

it('cancela evento e registra campos de cancelamento', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento para cancelar',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-07 09:00:00',
        'ends_at' => '2026-08-07 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/cancel', [
        'cancel_reason' => 'Paciente não poderá comparecer',
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['status'])->toBe('cancelado');
    expect($payload['data']['cancel_reason'])->toBe('Paciente não poderá comparecer');
    expect($payload['data']['canceled_at'])->not->toBeNull();
    expect($payload['data']['canceled_by_user_uuid'])->not->toBeNull();
});

it('evento cancelado não bloqueia horário posteriormente', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento base',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-08 09:00:00',
        'ends_at' => '2026-08-08 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/cancel', [
        'cancel_reason' => 'Cancelado',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento novo',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-08 09:30:00',
        'ends_at' => '2026-08-08 10:30:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('auditoria registra cancelamento de evento', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento cancelar auditoria',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-08-09 09:00:00',
        'ends_at' => '2026-08-09 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/cancel', [
        'cancel_reason' => 'Auditoria',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('schedule.events.canceled');
});

it('marca falta e audita', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento falta',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-08-10 09:00:00',
        'ends_at' => '2026-08-10 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/mark-absence', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['status'])->toBe('falta');
    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('schedule.events.absence_marked');
});

it('confirma evento e audita', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento confirmar',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-08-11 09:00:00',
        'ends_at' => '2026-08-11 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/confirm', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['status'])->toBe('confirmado');
    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('schedule.events.confirmed');
});

it('marca evento como realizado e audita sem criar atendimento real', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento realizado',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-08-12 09:00:00',
        'ends_at' => '2026-08-12 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/mark-done', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['status'])->toBe('realizado');
    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('schedule.events.done_marked');
});

it('remarca evento sem conflito', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento remarcável',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-13 09:00:00',
        'ends_at' => '2026-08-13 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/reschedule', [
        'starts_at' => '2026-08-13 11:00:00',
        'ends_at' => '2026-08-13 12:00:00',
        'reason' => 'Ajuste de agenda',
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect($payload['data']['status'])->toBe('remarcado');
    expect($payload['data']['starts_at'])->toBe('2026-08-13 11:00:00');
});

it('bloqueia remarcação com conflito', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);
    $professionalUuid = ScheduleApi::createProfessional($adminToken);
    $token = ScheduleApi::createLimitedUser('reschednoconflict', ['schedule.create', 'schedule.update']);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento fixo',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-14 09:00:00',
        'ends_at' => '2026-08-14 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento móvel',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-14 11:00:00',
        'ends_at' => '2026-08-14 12:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/reschedule', [
        'starts_at' => '2026-08-14 09:30:00',
        'ends_at' => '2026-08-14 10:30:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(409);
});

it('auditoria registra remarcação com antes e depois', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);

    $create = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Evento audit remarcacao',
        'event_type_uuid' => $eventTypeUuid,
        'starts_at' => '2026-08-15 09:00:00',
        'ends_at' => '2026-08-15 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);
    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/schedule/events/' . $uuid . '/reschedule', [
        'starts_at' => '2026-08-15 10:00:00',
        'ends_at' => '2026-08-15 11:00:00',
        'reason' => 'Mudança',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('schedule.events.rescheduled');
});

it('cria recorrência semanal simples e recurrence group uuid', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Recorrente semanal',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-17 09:00:00',
        'ends_at' => '2026-08-17 10:00:00',
        'recurrence' => [
            'frequency' => 'weekly',
            'week_days' => ['MO', 'WE'],
            'until' => '2026-08-31',
            'interval' => 1,
        ],
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(201);
    expect($payload['data']['occurrences_count'])->toBe(5);
    expect($payload['data']['recurrence_group_uuid'])->not->toBeNull();
});

it('bloqueia recorrência com conflito e não cria parcial', function (): void {
    $adminToken = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($adminToken);
    $professionalUuid = ScheduleApi::createProfessional($adminToken);
    $token = ScheduleApi::createLimitedUser('recurconflict', ['schedule.create']);

    ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Conflito quarta',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-19 09:00:00',
        'ends_at' => '2026-08-19 10:00:00',
    ], ['Authorization' => 'Bearer ' . $token]);

    $before = (int) DB::table('schedule_events')->count();

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Recorrente com conflito',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-08-17 09:00:00',
        'ends_at' => '2026-08-17 10:00:00',
        'recurrence' => [
            'frequency' => 'weekly',
            'week_days' => ['MO', 'WE'],
            'until' => '2026-08-31',
            'interval' => 1,
        ],
    ], ['Authorization' => 'Bearer ' . $token]);

    $after = (int) DB::table('schedule_events')->count();
    expect($res['status'])->toBe(409);
    expect($after)->toBe($before);
});

it('limita quantidade máxima de ocorrências recorrentes', function (): void {
    $token = ScheduleApi::tokenFor();
    $eventTypeUuid = ScheduleApi::createEventType($token);
    $professionalUuid = ScheduleApi::createProfessional($token);

    $res = ApiRequester::call('POST', '/api/v1/schedule/events', [
        'title' => 'Recorrência longa',
        'event_type_uuid' => $eventTypeUuid,
        'professional_uuid' => $professionalUuid,
        'starts_at' => '2026-01-05 09:00:00',
        'ends_at' => '2026-01-05 10:00:00',
        'recurrence' => [
            'frequency' => 'weekly',
            'week_days' => ['MO', 'WE', 'FR'],
            'until' => '2028-12-31',
            'interval' => 1,
        ],
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('lista tipos de evento e retorna array com itens criados', function (): void {
    $token = ScheduleApi::tokenFor();
    ScheduleApi::createEventType($token, ['name' => 'Tipo Alpha', 'category' => 'reuniao']);
    ScheduleApi::createEventType($token, ['name' => 'Tipo Beta', 'category' => 'bloqueio']);

    $res = ApiRequester::call('GET', '/api/v1/schedule/event-types', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect(count($json['data']))->toBeGreaterThanOrEqual(2);
});

it('retorna tipo de evento por uuid', function (): void {
    $token = ScheduleApi::tokenFor();
    $uuid = ScheduleApi::createEventType($token, ['name' => 'Tipo Get', 'color' => '#AABBCC']);

    $res = ApiRequester::call('GET', '/api/v1/schedule/event-types/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect($json['data']['name'])->toBe('Tipo Get');
    expect($json['data']['color'])->toBe('#AABBCC');
});

it('retorna 404 para tipo de evento inexistente', function (): void {
    $res = ApiRequester::call('GET', '/api/v1/schedule/event-types/uuid-nao-existe', [], [
        'Authorization' => 'Bearer ' . ScheduleApi::tokenFor(),
    ]);

    expect($res['status'])->toBe(404);
});

it('bloqueia categoria inválida em tipo de evento', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/schedule/event-types', [
        'name' => 'Tipo Inválido',
        'category' => 'categoria_nao_existe',
    ], ['Authorization' => 'Bearer ' . ScheduleApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('inativa tipo de evento via update', function (): void {
    $token = ScheduleApi::tokenFor();
    $uuid = ScheduleApi::createEventType($token, ['name' => 'Tipo Ativo', 'status' => 'ativo']);

    $res = ApiRequester::call('PUT', '/api/v1/schedule/event-types/' . $uuid, [
        'name' => 'Tipo Ativo',
        'status' => 'inativo',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
    expect(DB::table('schedule_event_types')->where('uuid', $uuid)->value('status'))->toBe('inativo');
});
