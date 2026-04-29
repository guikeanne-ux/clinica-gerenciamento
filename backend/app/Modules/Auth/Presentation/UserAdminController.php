<?php

declare(strict_types=1);

namespace App\Modules\Auth\Presentation;

use App\Core\Exceptions\HttpException;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\User;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\QueryException;

final class UserAdminController
{
    public function __construct(private readonly AuditService $auditService = new AuditService())
    {
    }

    public function create(Request $request): array
    {
        try {
            $user = User::query()->create([
                'uuid' => Uuid::v4(),
                'name' => (string) $request->input('name'),
                'login' => (string) $request->input('login'),
                'email' => (string) $request->input('email'),
                'password_hash' => password_hash((string) $request->input('password'), PASSWORD_ARGON2ID),
                'status' => 'active',
                'person_uuid' => null,
                'professional_uuid' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (QueryException $exception) {
            throw new HttpException('Login ou e-mail já existente.', 422);
        }

        $this->auditService->log('users.created', $request->attribute('auth_user')->uuid, ['user_uuid' => $user->uuid]);

        return JsonResponse::make(ApiResponse::success('Usuário criado com sucesso.', [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'login' => $user->login,
            'email' => $user->email,
            'status' => $user->status,
        ]), 201);
    }

    public function protectedUsersView(): array
    {
        return JsonResponse::make(ApiResponse::success(data: ['message' => 'ok']));
    }

    public function update(Request $request): array
    {
        $user = User::query()->where('uuid', $request->attribute('user_uuid'))->firstOrFail();
        $user->name = (string) $request->input('name', $user->name);
        $user->email = (string) $request->input('email', $user->email);
        $user->save();

        $this->auditService->log('users.updated', $request->attribute('auth_user')->uuid, ['user_uuid' => $user->uuid]);

        return JsonResponse::make(ApiResponse::success('Usuário atualizado com sucesso.'));
    }

    public function inactivate(Request $request): array
    {
        $user = User::query()->where('uuid', $request->attribute('user_uuid'))->firstOrFail();
        $user->status = 'inactive';
        $user->save();

        $this->auditService->log(
            'users.inactivated',
            $request->attribute('auth_user')->uuid,
            ['user_uuid' => $user->uuid]
        );

        return JsonResponse::make(ApiResponse::success('Usuário inativado com sucesso.'));
    }

    public function syncRoles(Request $request): array
    {
        $userUuid = (string) $request->attribute('user_uuid');
        $roleUuids = $request->input('roles', []);

        DB::table('user_roles')->where('user_uuid', $userUuid)->delete();
        foreach ($roleUuids as $roleUuid) {
            DB::table('user_roles')->insert([
                'uuid' => Uuid::v4(),
                'user_uuid' => $userUuid,
                'role_uuid' => (string) $roleUuid,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->auditService->log(
            'users.roles_changed',
            $request->attribute('auth_user')->uuid,
            ['user_uuid' => $userUuid]
        );

        return JsonResponse::make(ApiResponse::success('Perfis atualizados com sucesso.'));
    }

    public function syncPermissionOverrides(Request $request): array
    {
        $userUuid = (string) $request->attribute('user_uuid');
        $overrides = $request->input('overrides', []);

        DB::table('user_permission_overrides')->where('user_uuid', $userUuid)->delete();
        foreach ($overrides as $override) {
            DB::table('user_permission_overrides')->insert([
                'uuid' => Uuid::v4(),
                'user_uuid' => $userUuid,
                'permission_uuid' => (string) ($override['permission_uuid'] ?? ''),
                'is_allowed' => (bool) ($override['is_allowed'] ?? false),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->auditService->log(
            'users.permissions_changed',
            $request->attribute('auth_user')->uuid,
            ['user_uuid' => $userUuid]
        );

        return JsonResponse::make(ApiResponse::success('Permissões do usuário atualizadas com sucesso.'));
    }
}
