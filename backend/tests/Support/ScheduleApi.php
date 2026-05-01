<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class ScheduleApi
{
    public static function tokenFor(string $login = 'admin', string $password = 'admin123'): string
    {
        $response = ApiRequester::call('POST', '/api/v1/auth/login', [
            'login' => $login,
            'password' => $password,
        ]);

        $payload = json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR);
        $token = $payload['data']['token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Falha ao autenticar usuário de teste: ' . $login);
        }

        return $token;
    }

    public static function createLimitedUser(string $login, array $allowedPermissions = []): string
    {
        return PersonApi::createLimitedUser($login, $allowedPermissions);
    }

    public static function createPatient(string $token, string $name = 'Paciente Agenda'): string
    {
        $suffix = (string) random_int(1000, 9999);
        $response = ApiRequester::call('POST', '/api/v1/patients', [
            'full_name' => $name . ' ' . $suffix,
            'birth_date' => '2016-01-01',
            'cpf' => (string) random_int(10000000000, 99999999999),
            'email' => 'paciente' . $suffix . '@agenda.local',
        ], ['Authorization' => 'Bearer ' . $token]);

        return json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    }

    public static function createProfessional(string $token, string $name = 'Profissional Agenda'): string
    {
        $suffix = (string) random_int(1000, 9999);
        $response = ApiRequester::call('POST', '/api/v1/professionals', [
            'full_name' => $name . ' ' . $suffix,
            'cpf' => (string) random_int(10000000000, 99999999999),
            'email' => 'prof' . $suffix . '@agenda.local',
        ], ['Authorization' => 'Bearer ' . $token]);

        return json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    }

    public static function createEventType(string $token, array $override = []): string
    {
        $payload = array_merge([
            'name' => 'Atendimento Padrão',
            'description' => 'Tipo padrão para testes',
            'category' => 'atendimento',
            'color' => '#4477AA',
            'requires_patient' => false,
            'requires_professional' => false,
            'can_generate_attendance' => false,
            'can_generate_financial_entry' => false,
            'status' => 'ativo',
        ], $override);

        $response = ApiRequester::call('POST', '/api/v1/schedule/event-types', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        return json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    }

    public static function createEvent(string $token, array $payload): array
    {
        $response = ApiRequester::call('POST', '/api/v1/schedule/events', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        return json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR);
    }

    public static function userUuidByLogin(string $login): ?string
    {
        $value = DB::table('users')->where('login', $login)->value('uuid');

        return $value !== null ? (string) $value : null;
    }

    public static function attachProfessionalToUser(string $login, string $professionalUuid): void
    {
        DB::table('users')->where('login', $login)->update([
            'professional_uuid' => $professionalUuid,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function grantPermissionToUser(string $login, string $permissionCode): void
    {
        $userUuid = self::userUuidByLogin($login);
        if ($userUuid === null) {
            return;
        }

        $permissionUuid = DB::table('permissions')->where('code', $permissionCode)->value('uuid');
        if ($permissionUuid === null) {
            return;
        }

        DB::table('user_permission_overrides')->updateOrInsert([
            'user_uuid' => $userUuid,
            'permission_uuid' => $permissionUuid,
        ], [
            'uuid' => DB::table('user_permission_overrides')
                ->where('user_uuid', $userUuid)
                ->where('permission_uuid', $permissionUuid)
                ->value('uuid') ?? Uuid::v4(),
            'user_uuid' => $userUuid,
            'permission_uuid' => $permissionUuid,
            'is_allowed' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
