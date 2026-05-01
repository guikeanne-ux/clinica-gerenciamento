<?php

declare(strict_types=1);

use App\Core\Database\DatabaseManager;
use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;
use Tests\Support\ApiRequester;
use Tests\Support\PersonApi;

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

it('cria paciente válido', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Teste',
        'birth_date' => '2015-01-01',
        'cpf' => '12345678901',
        'email' => 'paciente@teste.com',
        'phone_primary' => '11999999999',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(201);
});

it('bloqueia paciente sem nome', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/patients', [
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('bloqueia paciente sem data de nascimento', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Teste',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('bloqueia cpf inválido de paciente', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Teste',
        'birth_date' => '2015-01-01',
        'cpf' => '123',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('bloqueia cpf duplicado de paciente', function (): void {
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente A',
        'birth_date' => '2010-01-01',
        'cpf' => '12345678901',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente B',
        'birth_date' => '2011-01-01',
        'cpf' => '12345678901',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(409);
});

it('edita paciente', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Nome Antigo',
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('PUT', '/api/v1/patients/' . $uuid, [
        'full_name' => 'Nome Novo',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
    expect(DB::table('patients')->where('uuid', $uuid)->value('full_name'))->toBe('Nome Novo');
});

it('lista pacientes com paginação', function (): void {
    $token = PersonApi::tokenFor();

    for ($i = 1; $i <= 3; $i++) {
        ApiRequester::call('POST', '/api/v1/patients', [
            'full_name' => 'Paciente ' . $i,
            'birth_date' => '2015-01-0' . $i,
        ], ['Authorization' => 'Bearer ' . $token]);
    }

    $res = ApiRequester::call('GET', '/api/v1/patients?page=1&per_page=2', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect(count($json['data']['items']))->toBe(2);
    expect($json['data']['pagination']['total'])->toBe(3);
});

it('busca paciente por nome cpf email telefone', function (): void {
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Maria Busca',
        'birth_date' => '2014-01-01',
        'cpf' => '98765432100',
        'email' => 'maria@busca.com',
        'phone_primary' => '21988887777',
    ], ['Authorization' => 'Bearer ' . $token]);

    $byName = ApiRequester::call(
        'GET',
        '/api/v1/patients?search=Maria',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );
    $byCpf = ApiRequester::call(
        'GET',
        '/api/v1/patients?search=98765432100',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );
    $byEmail = ApiRequester::call(
        'GET',
        '/api/v1/patients?search=maria@busca.com',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );
    $byPhone = ApiRequester::call(
        'GET',
        '/api/v1/patients?search=21988887777',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    $namePayload = json_decode($byName['body'], true, flags: JSON_THROW_ON_ERROR);
    $cpfPayload = json_decode($byCpf['body'], true, flags: JSON_THROW_ON_ERROR);
    $emailPayload = json_decode($byEmail['body'], true, flags: JSON_THROW_ON_ERROR);
    $phonePayload = json_decode($byPhone['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($namePayload['data']['pagination']['total'])->toBeGreaterThan(0);
    expect($cpfPayload['data']['pagination']['total'])->toBeGreaterThan(0);
    expect($emailPayload['data']['pagination']['total'])->toBeGreaterThan(0);
    expect($phonePayload['data']['pagination']['total'])->toBeGreaterThan(0);
});

it('faz soft delete de paciente', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Delete',
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('DELETE', '/api/v1/patients/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
    expect(DB::table('patients')->where('uuid', $uuid)->value('deleted_at'))->not->toBeNull();
});

it('bloqueia acesso de pacientes sem permissão', function (): void {
    $token = PersonApi::createLimitedUser('nopatient', []);

    $res = ApiRequester::call('GET', '/api/v1/patients', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});

it('auditoria registra criação edição exclusão de paciente', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Audit',
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('PUT', '/api/v1/patients/' . $uuid, [
        'full_name' => 'Paciente Audit Atualizado',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('DELETE', '/api/v1/patients/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $events = DB::table('audit_logs')->pluck('event')->all();

    expect($events)->toContain('patients.created');
    expect($events)->toContain('patients.updated');
    expect($events)->toContain('patients.deleted');
});

it('cria responsável válido', function (): void {
    $token = PersonApi::tokenFor();

    $patient = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Base',
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $patientUuid = json_decode($patient['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/patients/' . $patientUuid . '/responsibles', [
        'name' => 'Mae Teste',
        'cpf' => '12345678901',
        'phone' => '11999999999',
        'email' => 'mae@teste.com',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
});

it('bloqueia responsável para paciente inexistente', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/patients/' . Uuid::v4() . '/responsibles', [
        'name' => 'Responsavel',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(404);
});

it('bloqueia cpf inválido de responsável', function (): void {
    $token = PersonApi::tokenFor();

    $patient = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Base',
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $patientUuid = json_decode($patient['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/patients/' . $patientUuid . '/responsibles', [
        'name' => 'Responsavel',
        'cpf' => '123',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('edita responsável', function (): void {
    $token = PersonApi::tokenFor();

    $patient = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Base',
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $patientUuid = json_decode($patient['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $create = ApiRequester::call('POST', '/api/v1/patients/' . $patientUuid . '/responsibles', [
        'name' => 'Pai Antigo',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('PUT', '/api/v1/patient-responsibles/' . $uuid, [
        'name' => 'Pai Novo',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
});

it('faz soft delete de responsável', function (): void {
    $token = PersonApi::tokenFor();

    $patient = ApiRequester::call('POST', '/api/v1/patients', [
        'full_name' => 'Paciente Base',
        'birth_date' => '2015-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $patientUuid = json_decode($patient['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $create = ApiRequester::call('POST', '/api/v1/patients/' . $patientUuid . '/responsibles', [
        'name' => 'Delete Resp',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('DELETE', '/api/v1/patient-responsibles/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
    expect(DB::table('patient_responsibles')->where('uuid', $uuid)->value('deleted_at'))->not->toBeNull();
});

it('cria profissional válido', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Profissional A',
        'cpf' => '12345678901',
        'email' => 'profa@clinica.local',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(201);
});

it('gera cor automaticamente para profissional', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Profissional Cor',
        'cpf' => '12345678902',
        'email' => 'profcor@clinica.local',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(201);
    expect($json['data']['schedule_color'])->not->toBeNull();
});

it('aceita cor de agenda definida na criação do profissional', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Profissional Cor Definida',
        'cpf' => '12345678919',
        'email' => 'profcordefinida@clinica.local',
        'schedule_color' => '#FF5733',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(201);
    expect($json['data']['schedule_color'])->toBe('#FF5733');
});

it('atualiza cor de agenda do profissional', function (): void {
    $token = PersonApi::tokenFor();

    $createRes = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Profissional Cor Update',
        'cpf' => '12345678920',
        'email' => 'profcorupdate@clinica.local',
    ], ['Authorization' => 'Bearer ' . $token]);

    $createJson = json_decode($createRes['body'], true, flags: JSON_THROW_ON_ERROR);
    $uuid = $createJson['data']['uuid'];

    $res = ApiRequester::call('PUT', '/api/v1/professionals/' . $uuid, [
        'schedule_color' => '#1A2B3C',
    ], ['Authorization' => 'Bearer ' . $token]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(200);
    expect($json['data']['schedule_color'])->toBe('#1A2B3C');
});

it('bloqueia cpf duplicado de profissional', function (): void {
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Prof A',
        'cpf' => '12345678903',
        'email' => 'profa1@clinica.local',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Prof B',
        'cpf' => '12345678903',
        'email' => 'profa2@clinica.local',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(409);
});

it('bloqueia e-mail inválido de profissional', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Prof Invalido',
        'cpf' => '12345678904',
        'email' => 'invalido',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('cria profissional também como usuário', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Prof User',
        'cpf' => '12345678905',
        'email' => 'profuser@clinica.local',
        'also_user' => true,
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    $user = DB::table('users')->where('professional_uuid', $json['data']['uuid'])->first();

    expect($res['status'])->toBe(201);
    expect($json['data']['user_uuid'])->not->toBeNull();
    expect($user)->not->toBeNull();
});

it('cria usuário posteriormente para profissional existente', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Prof Depois',
        'cpf' => '12345678906',
        'email' => 'profdepois@clinica.local',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $uuid . '/create-user', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
});

it('bloqueia criação de usuário duplicado para profissional', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Prof Dup User',
        'cpf' => '12345678907',
        'email' => 'profdup@clinica.local',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/professionals/' . $uuid . '/create-user', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $uuid . '/create-user', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(409);
});

it('bloqueia acesso de profissionais sem permissão', function (): void {
    $token = PersonApi::createLimitedUser('noprof', []);

    $res = ApiRequester::call('GET', '/api/v1/professionals', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});

it('auditoria registra vínculo profissional usuário', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/professionals', [
        'full_name' => 'Prof Audit',
        'cpf' => '12345678908',
        'email' => 'profaudit@clinica.local',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/professionals/' . $uuid . '/create-user', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('professionals.user_linked');
});

it('cria fornecedor válido', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/suppliers', [
        'name_or_legal_name' => 'Fornecedor A',
        'document' => '12345678901',
        'email' => 'forn@clinica.local',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(201);
});

it('bloqueia cpf cnpj inválido de fornecedor', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/suppliers', [
        'name_or_legal_name' => 'Fornecedor X',
        'document' => '123',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('bloqueia documento duplicado de fornecedor', function (): void {
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/suppliers', [
        'name_or_legal_name' => 'Fornecedor A',
        'document' => '12345678909',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/suppliers', [
        'name_or_legal_name' => 'Fornecedor B',
        'document' => '12345678909',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(409);
});

it('edita fornecedor', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/suppliers', [
        'name_or_legal_name' => 'Fornecedor Antigo',
        'document' => '12345678910',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('PUT', '/api/v1/suppliers/' . $uuid, [
        'name_or_legal_name' => 'Fornecedor Novo',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
});

it('faz soft delete de fornecedor', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/suppliers', [
        'name_or_legal_name' => 'Fornecedor Delete',
        'document' => '12345678911',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('DELETE', '/api/v1/suppliers/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
    expect(DB::table('suppliers')->where('uuid', $uuid)->value('deleted_at'))->not->toBeNull();
});

it('bloqueia acesso de fornecedores sem permissão', function (): void {
    $token = PersonApi::createLimitedUser('nosupp', []);

    $res = ApiRequester::call('GET', '/api/v1/suppliers', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});
