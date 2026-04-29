<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;

final class PersonApi
{
    public static function tokenFor(string $login = 'admin', string $password = 'admin123'): string
    {
        $res = ApiRequester::call('POST', '/api/v1/auth/login', [
            'login' => $login,
            'password' => $password,
        ]);

        return json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR)['data']['token'];
    }

    public static function createLimitedUser(string $login, array $allowedPermissions = []): string
    {
        $userUuid = Uuid::v4();

        DB::table('users')->insert([
            'uuid' => $userUuid,
            'name' => 'Limited User',
            'login' => $login,
            'email' => $login . '@clinica.local',
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

        foreach ($allowedPermissions as $permission) {
            $permissionUuid = DB::table('permissions')->where('code', $permission)->value('uuid');
            if ($permissionUuid !== null) {
                DB::table('user_permission_overrides')->insert([
                    'uuid' => Uuid::v4(),
                    'user_uuid' => $userUuid,
                    'permission_uuid' => $permissionUuid,
                    'is_allowed' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return self::tokenFor($login, '123456');
    }
}
