<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application;

use App\Core\Exceptions\HttpException;
use App\Core\Support\Uuid;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Infrastructure\Models\FailedLoginAttempt;
use App\Modules\Auth\Infrastructure\Models\User;

final class AuthService
{
    public function __construct(
        private readonly JwtService $jwtService = new JwtService(),
        private readonly PermissionService $permissionService = new PermissionService(),
        private readonly AuditService $auditService = new AuditService()
    ) {
    }

    public function login(string $login, string $password): array
    {
        $user = User::query()->where('login', $login)->orWhere('email', $login)->first();

        if (! $user || ! password_verify($password, $user->password_hash)) {
            FailedLoginAttempt::query()->create([
                'uuid' => Uuid::v4(),
                'login' => $login,
                'ip_address' => '127.0.0.1',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->auditService->log('auth.login_failed', $user?->uuid, ['login' => $login]);
            throw new HttpException('Credenciais inválidas.', 401);
        }

        if ($user->status !== 'active') {
            $this->auditService->log('auth.login_blocked_inactive', $user->uuid);
            throw new HttpException('Usuário inativo.', 401);
        }

        if ($user->roles()->count() === 0) {
            throw new HttpException('Usuário sem perfil vinculado.', 403);
        }

        $user->last_access_at = date('Y-m-d H:i:s');
        $user->save();

        $token = $this->jwtService->encode($user->uuid);
        $this->auditService->log('auth.login', $user->uuid);

        return [
            'token' => $token,
            'user' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'status' => $user->status,
                'permissions' => $this->permissionService->permissionsFor($user),
            ],
        ];
    }

    public function changePassword(User $user, string $current, string $next): void
    {
        if (! password_verify($current, $user->password_hash)) {
            throw new HttpException('Senha atual inválida.', 422);
        }

        $user->password_hash = password_hash($next, PASSWORD_ARGON2ID);
        $user->save();

        $this->auditService->log('auth.password_changed', $user->uuid);
    }
}
