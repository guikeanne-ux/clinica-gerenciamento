<?php

declare(strict_types=1);

namespace App\Modules\ACL\Application;

use App\Modules\Auth\Infrastructure\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

final class PermissionService
{
    public function permissionsFor(User $user): array
    {
        $rolePermissions = DB::table('permissions')
            ->join('role_permissions', 'permissions.uuid', '=', 'role_permissions.permission_uuid')
            ->join('user_roles', 'role_permissions.role_uuid', '=', 'user_roles.role_uuid')
            ->where('user_roles.user_uuid', '=', $user->uuid)
            ->pluck('permissions.code')
            ->all();

        $overrides = DB::table('user_permission_overrides')
            ->join('permissions', 'permissions.uuid', '=', 'user_permission_overrides.permission_uuid')
            ->where('user_permission_overrides.user_uuid', '=', $user->uuid)
            ->select('permissions.code', 'user_permission_overrides.is_allowed')
            ->get();

        $granted = array_fill_keys($rolePermissions, true);
        foreach ($overrides as $override) {
            $granted[$override->code] = (bool) $override->is_allowed;
        }

        return array_keys(array_filter($granted, fn ($v) => $v === true));
    }

    public function has(User $user, string $permission): bool
    {
        return in_array($permission, $this->permissionsFor($user), true);
    }
}
