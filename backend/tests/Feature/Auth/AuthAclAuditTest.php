<?php

declare(strict_types=1);

use App\Core\Database\DatabaseManager;
use App\Core\Http\Kernel;
use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;
use Tests\Support\ApiRequester;

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

it('login with valid credentials', function (): void {
    $response = ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'admin', 'password' => 'admin123']);
    $payload = json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($response['status'])->toBe(200);
    expect($payload['data']['token'])->not->toBeEmpty();
});

it('login with invalid credentials', function (): void {
    $response = ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'admin', 'password' => 'wrong']);
    expect($response['status'])->toBe(401);
});

it('inactive user cannot login', function (): void {
    DB::table('users')->where('login', 'admin')->update(['status' => 'inactive']);
    $response = ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'admin', 'password' => 'admin123']);
    expect($response['status'])->toBe(401);
});

it('protected route without token returns 401', function (): void {
    $response = ApiRequester::call('GET', '/api/v1/auth/me');
    expect($response['status'])->toBe(401);
});

it('route with user without permission returns 403', function (): void {
    $userUuid = Uuid::v4();

    DB::table('users')->insert([
        'uuid' => $userUuid,
        'name' => 'Sem Permissao',
        'login' => 'semperm',
        'email' => 'semperm@clinica.local',
        'password_hash' => password_hash('123456', PASSWORD_ARGON2ID),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $roleUuid = DB::table('roles')->where('name', 'Auditor/leitura')->value('uuid');
    DB::table('user_roles')->insert([
        'uuid' => Uuid::v4(),
        'user_uuid' => $userUuid,
        'role_uuid' => $roleUuid,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $login = ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'semperm', 'password' => '123456']);
    $token = json_decode($login['body'], true, flags: JSON_THROW_ON_ERROR)['data']['token'];

    $response = ApiRequester::call('GET', '/api/v1/admin/users', [], ['Authorization' => 'Bearer ' . $token]);
    expect($response['status'])->toBe(403);
});

it('user with permission accesses protected route', function (): void {
    $login = ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'admin', 'password' => 'admin123']);
    $token = json_decode($login['body'], true, flags: JSON_THROW_ON_ERROR)['data']['token'];

    $response = ApiRequester::call('GET', '/api/v1/admin/users', [], ['Authorization' => 'Bearer ' . $token]);
    expect($response['status'])->toBe(200);
});

it('duplicate login is blocked', function (): void {
    $login = ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'admin', 'password' => 'admin123']);
    $token = json_decode($login['body'], true, flags: JSON_THROW_ON_ERROR)['data']['token'];

    $response = ApiRequester::call('POST', '/api/v1/admin/users', [
        'name' => 'Outro Admin',
        'login' => 'admin',
        'email' => 'novo@clinica.local',
        'password' => 'abc12345',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($response['status'])->toBe(409);
});

it('audit registers critical events', function (): void {
    ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'admin', 'password' => 'wrong']);
    $login = ApiRequester::call('POST', '/api/v1/auth/login', ['login' => 'admin', 'password' => 'admin123']);
    $token = json_decode($login['body'], true, flags: JSON_THROW_ON_ERROR)['data']['token'];

    ApiRequester::call('POST', '/api/v1/auth/logout', [], ['Authorization' => 'Bearer ' . $token]);

    $events = DB::table('audit_logs')->pluck('event')->all();

    expect($events)->toContain('auth.login_failed');
    expect($events)->toContain('auth.login');
    expect($events)->toContain('auth.logout');
});
