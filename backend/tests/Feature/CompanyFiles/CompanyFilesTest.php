<?php

declare(strict_types=1);

use App\Core\Database\DatabaseManager;
use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;
use Tests\Support\CompanyFilesApi;

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

it('consultar empresa autenticado e com permissão', function (): void {
    $res = CompanyFilesApi::call(
        'GET',
        '/api/v1/company',
        [],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect($res['status'])->toBe(200);
});

it('bloquear consulta de empresa sem permissão', function (): void {
    $userUuid = Uuid::v4();

    DB::table('users')->insert([
        'uuid' => $userUuid,
        'name' => 'NoCompany',
        'login' => 'nocomp',
        'email' => 'nocomp@c.local',
        'password_hash' => password_hash('123456', PASSWORD_ARGON2ID),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $auditorRole = DB::table('roles')->where('name', 'Auditor/leitura')->value('uuid');

    DB::table('user_roles')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'role_uuid' => $auditorRole,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $token = CompanyFilesApi::loginToken('nocomp', '123456');
    $res = CompanyFilesApi::call('GET', '/api/v1/company', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});

it('atualizar empresa com dados válidos', function (): void {
    $res = CompanyFilesApi::call(
        'PUT',
        '/api/v1/company',
        [
            'legal_name' => 'Clinica X',
            'document' => '12.345.678/0001-00',
            'email' => 'contato@x.com',
            'phone' => '(11) 99999-9999',
        ],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect($res['status'])->toBe(200);
});

it('bloquear documento inválido', function (): void {
    $res = CompanyFilesApi::call(
        'PUT',
        '/api/v1/company',
        ['document' => '123'],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect($res['status'])->toBe(422);
});

it('bloquear e-mail inválido', function (): void {
    $res = CompanyFilesApi::call(
        'PUT',
        '/api/v1/company',
        ['email' => 'invalido'],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect($res['status'])->toBe(422);
});

it('upload válido salva blob e checksum', function (): void {
    $payload = [
        'original_name' => 'logo.png',
        'mime_type' => 'image/png',
        'content_base64' => base64_encode('fake_png_content'),
        'classification' => 'logo_principal',
    ];

    $res = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        $payload,
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($res['status'])->toBe(201);
    expect($json['data']['checksum_hash'])->not->toBeEmpty();
});

it('upload inválido por extensão é bloqueado', function (): void {
    $res = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        [
            'original_name' => 'virus.exe',
            'mime_type' => 'application/pdf',
            'content_base64' => base64_encode('abc'),
        ],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect($res['status'])->toBe(422);
});

it('upload inválido por mime é bloqueado', function (): void {
    $res = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        [
            'original_name' => 'logo.png',
            'mime_type' => 'application/octet-stream',
            'content_base64' => base64_encode('abc'),
        ],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect($res['status'])->toBe(422);
});

it('upload acima do limite é bloqueado', function (): void {
    $res = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        [
            'original_name' => 'big.pdf',
            'mime_type' => 'application/pdf',
            'content_base64' => base64_encode(str_repeat('a', 5_242_881)),
        ],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect($res['status'])->toBe(422);
});

it('download sem permissão retorna 403', function (): void {
    $adminToken = CompanyFilesApi::loginToken();

    $upload = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        [
            'original_name' => 'a.pdf',
            'mime_type' => 'application/pdf',
            'content_base64' => base64_encode('pdf'),
        ],
        ['Authorization' => 'Bearer ' . $adminToken]
    );

    $uuid = json_decode($upload['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    $userUuid = Uuid::v4();

    DB::table('users')->insert([
        'uuid' => $userUuid,
        'name' => 'NoDown',
        'login' => 'nodown',
        'email' => 'nodown@c.local',
        'password_hash' => password_hash('123456', PASSWORD_ARGON2ID),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $auditorRole = DB::table('roles')->where('name', 'Auditor/leitura')->value('uuid');

    DB::table('user_roles')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'role_uuid' => $auditorRole,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $permUuid = DB::table('permissions')->where('code', 'files.download')->value('uuid');

    DB::table('user_permission_overrides')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'permission_uuid' => $permUuid,
        'is_allowed' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $token = CompanyFilesApi::loginToken('nodown', '123456');

    $res = CompanyFilesApi::call(
        'GET',
        '/api/v1/files/' . $uuid . '/download',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    expect($res['status'])->toBe(403);
});

it('download com permissão funciona', function (): void {
    $token = CompanyFilesApi::loginToken();

    $upload = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        [
            'original_name' => 'a.pdf',
            'mime_type' => 'application/pdf',
            'content_base64' => base64_encode('pdf'),
        ],
        ['Authorization' => 'Bearer ' . $token]
    );

    $uuid = json_decode($upload['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = CompanyFilesApi::call(
        'GET',
        '/api/v1/files/' . $uuid . '/download',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    expect($res['status'])->toBe(200);
});

it('exclusão de arquivo é lógica', function (): void {
    $token = CompanyFilesApi::loginToken();

    $upload = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        [
            'original_name' => 'a.pdf',
            'mime_type' => 'application/pdf',
            'content_base64' => base64_encode('pdf'),
        ],
        ['Authorization' => 'Bearer ' . $token]
    );

    $uuid = json_decode($upload['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = CompanyFilesApi::call(
        'DELETE',
        '/api/v1/files/' . $uuid,
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    expect($res['status'])->toBe(200);
    expect(DB::table('files')->where('uuid', $uuid)->value('deleted_at'))->not->toBeNull();
});

it('auditoria registra alteração da empresa', function (): void {
    CompanyFilesApi::call(
        'PUT',
        '/api/v1/company',
        ['legal_name' => 'C1'],
        ['Authorization' => 'Bearer ' . CompanyFilesApi::loginToken()]
    );

    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('company.updated');
});

it('auditoria registra upload download exclusão', function (): void {
    $token = CompanyFilesApi::loginToken();

    $upload = CompanyFilesApi::call(
        'POST',
        '/api/v1/files/upload',
        [
            'original_name' => 'a.pdf',
            'mime_type' => 'application/pdf',
            'content_base64' => base64_encode('pdf'),
        ],
        ['Authorization' => 'Bearer ' . $token]
    );

    $uuid = json_decode($upload['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    CompanyFilesApi::call(
        'GET',
        '/api/v1/files/' . $uuid . '/download',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    CompanyFilesApi::call(
        'DELETE',
        '/api/v1/files/' . $uuid,
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    $events = DB::table('audit_logs')->pluck('event')->all();
    expect($events)->toContain('files.uploaded');
    expect($events)->toContain('files.downloaded');
    expect($events)->toContain('files.deleted');
});
