<?php

declare(strict_types=1);

namespace App\Modules\ACL\Infrastructure\Middleware;

use App\Core\Exceptions\AuthorizationException;
use App\Modules\ACL\Application\PermissionService;
use App\Modules\Auth\Infrastructure\Models\User;

final class PermissionMiddleware
{
    public function __construct(private readonly PermissionService $permissionService = new PermissionService())
    {
    }

    public function handle(User $user, string $permission): void
    {
        if (! $this->permissionService->has($user, $permission)) {
            throw new AuthorizationException('Você não tem permissão para acessar esta área.');
        }
    }
}
