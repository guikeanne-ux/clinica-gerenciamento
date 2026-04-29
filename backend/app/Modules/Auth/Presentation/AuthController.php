<?php

declare(strict_types=1);

namespace App\Modules\Auth\Presentation;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Support\ApiResponse;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Auth\Application\AuthService;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService = new AuthService(),
        private readonly PermissionService $permissionService = new PermissionService(),
        private readonly AuditService $auditService = new AuditService()
    ) {
    }

    public function login(Request $request): array
    {
        $data = $this->authService->login((string) $request->input('login'), (string) $request->input('password'));
        return JsonResponse::make(ApiResponse::success('Login realizado com sucesso.', $data), 200);
    }

    public function me(Request $request): array
    {
        $user = $request->attribute('auth_user');

        return JsonResponse::make(ApiResponse::success(data: [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'login' => $user->login,
            'email' => $user->email,
            'status' => $user->status,
            'permissions' => $this->permissionService->permissionsFor($user),
        ]));
    }

    public function logout(Request $request): array
    {
        $user = $request->attribute('auth_user');
        $this->auditService->log('auth.logout', $user->uuid);
        return JsonResponse::make(ApiResponse::success('Logout realizado com sucesso.'));
    }

    public function changePassword(Request $request): array
    {
        $user = $request->attribute('auth_user');
        $this->authService->changePassword(
            $user,
            (string) $request->input('current_password'),
            (string) $request->input('new_password')
        );

        return JsonResponse::make(ApiResponse::success('Senha alterada com sucesso.'));
    }
}
