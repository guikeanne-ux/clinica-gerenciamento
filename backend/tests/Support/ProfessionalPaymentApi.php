<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;

final class ProfessionalPaymentApi
{
    public static function createProfessional(
        string $cpf = '12345678901',
        string $email = 'profpay@clinica.local'
    ): string {
        $res = ApiRequester::call('POST', '/api/v1/professionals', [
            'full_name' => 'Profissional Repasse',
            'cpf' => $cpf,
            'email' => $email,
        ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

        return json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    }

    public static function createProfessionalClinicoUser(
        string $login = 'profclin',
        string $email = 'profclin@clinica.local'
    ): string {
        $userUuid = Uuid::v4();

        DB::table('users')->insert([
            'uuid' => $userUuid,
            'name' => 'Prof Clinico',
            'login' => $login,
            'email' => $email,
            'password_hash' => password_hash('123456', PASSWORD_ARGON2ID),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $roleUuid = DB::table('roles')->where('name', 'Profissional clínico')->value('uuid');
        DB::table('user_roles')->insert([
            'uuid' => Uuid::v4(),
            'user_uuid' => $userUuid,
            'role_uuid' => $roleUuid,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return PersonApi::tokenFor($login, '123456');
    }
}
