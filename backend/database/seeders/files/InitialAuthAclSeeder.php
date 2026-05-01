<?php

declare(strict_types=1);

use App\Core\Support\Uuid;
use Illuminate\Database\Capsule\Manager as DB;

return static function (): void {
    $roles = [
        'Administrador do sistema',
        'Direção',
        'Financeiro',
        'Secretária/Recepção',
        'Profissional clínico',
        'Contas médicas',
        'RH',
        'Auditor/leitura',
    ];

    $permissions = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'acl.manage',
        'audit.view',
        'company.view',
        'company.update',
        'files.upload',
        'files.view',
        'files.download',
        'files.delete',
        'patients.view',
        'patients.create',
        'patients.update',
        'patients.delete',
        'professionals.view',
        'professionals.create',
        'professionals.update',
        'professionals.delete',
        'suppliers.view',
        'suppliers.create',
        'suppliers.update',
        'suppliers.delete',
        'professional_payment.view',
        'professional_payment.create',
        'professional_payment.update',
        'professional_payment.delete',
        'professional_payment.simulate',
        'schedule.view',
        'schedule.view_all',
        'schedule.create',
        'schedule.update',
        'schedule.cancel',
        'schedule.delete',
        'schedule.override_conflict',
        'schedule.create_attendance',
        'schedule.event_types.view',
        'schedule.event_types.create',
        'schedule.event_types.update',
        'schedule.event_types.delete',
        'attendance.view',
        'attendance.create',
        'attendance.update_own',
        'attendance.update_all',
        'attendance.cancel',
        'attendance.mark_no_show',
        'attendance.finalize',
        'attendance.start_from_schedule',
        'attendance.substitute_professional',
        'clinical_record.view_own',
        'clinical_record.view_all',
        'clinical_record.create',
        'clinical_record.update_own',
        'clinical_record.update_all',
        'clinical_record.finalize',
        'clinical_record.complement',
        'clinical_record.cancel',
        'patient_timeline.view',
        'audio_record.create',
        'audio_record.view',
        'audio_record.delete',
    ];

    foreach ($roles as $role) {
        DB::table('roles')->updateOrInsert(['name' => $role], [
            'uuid' => DB::table('roles')->where('name', $role)->value('uuid') ?? Uuid::v4(),
            'name' => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    foreach ($permissions as $permission) {
        DB::table('permissions')->updateOrInsert(['code' => $permission], [
            'uuid' => DB::table('permissions')->where('code', $permission)->value('uuid') ?? Uuid::v4(),
            'code' => $permission,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    $adminUserUuid = DB::table('users')->where('login', 'admin')->value('uuid') ?? Uuid::v4();
    DB::table('users')->updateOrInsert(['login' => 'admin'], [
        'uuid' => $adminUserUuid,
        'name' => 'Administrador',
        'login' => 'admin',
        'email' => 'admin@clinica.local',
        'password_hash' => password_hash('admin123', PASSWORD_ARGON2ID),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $adminRole = DB::table('roles')->where('name', 'Administrador do sistema')->first();
    DB::table('user_roles')->updateOrInsert([
        'user_uuid' => $adminUserUuid,
        'role_uuid' => $adminRole->uuid,
    ], [
        'uuid' => DB::table('user_roles')->where('user_uuid', $adminUserUuid)->where('role_uuid', $adminRole->uuid)->value('uuid') ?? Uuid::v4(),
        'user_uuid' => $adminUserUuid,
        'role_uuid' => $adminRole->uuid,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $allPermissions = DB::table('permissions')->get();
    foreach ($allPermissions as $permission) {
        DB::table('role_permissions')->updateOrInsert([
            'role_uuid' => $adminRole->uuid,
            'permission_uuid' => $permission->uuid,
        ], [
            'uuid' => DB::table('role_permissions')->where('role_uuid', $adminRole->uuid)->where('permission_uuid', $permission->uuid)->value('uuid') ?? Uuid::v4(),
            'role_uuid' => $adminRole->uuid,
            'permission_uuid' => $permission->uuid,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    $directionRole = DB::table('roles')->where('name', 'Direção')->first();
    if ($directionRole !== null) {
        $directionPermissions = DB::table('permissions')
            ->whereIn('code', [
                'professional_payment.view',
                'professional_payment.create',
                'professional_payment.update',
                'professional_payment.delete',
                'professional_payment.simulate',
                'schedule.view',
                'schedule.view_all',
                'schedule.create',
                'schedule.update',
                'schedule.cancel',
                'schedule.delete',
                'schedule.override_conflict',
                'schedule.create_attendance',
                'schedule.event_types.view',
                'schedule.event_types.create',
                'schedule.event_types.update',
                'schedule.event_types.delete',
                'attendance.view',
                'attendance.create',
                'attendance.update_own',
                'attendance.update_all',
                'attendance.cancel',
                'attendance.mark_no_show',
                'attendance.finalize',
                'attendance.start_from_schedule',
                'attendance.substitute_professional',
                'clinical_record.view_own',
                'clinical_record.view_all',
                'clinical_record.create',
                'clinical_record.update_own',
                'clinical_record.update_all',
                'clinical_record.finalize',
                'clinical_record.complement',
                'clinical_record.cancel',
                'patient_timeline.view',
                'audio_record.create',
                'audio_record.view',
                'audio_record.delete',
            ])
            ->get();

        foreach ($directionPermissions as $permission) {
            DB::table('role_permissions')->updateOrInsert([
                'role_uuid' => $directionRole->uuid,
                'permission_uuid' => $permission->uuid,
            ], [
                'uuid' => DB::table('role_permissions')
                    ->where('role_uuid', $directionRole->uuid)
                    ->where('permission_uuid', $permission->uuid)
                    ->value('uuid') ?? Uuid::v4(),
                'role_uuid' => $directionRole->uuid,
                'permission_uuid' => $permission->uuid,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    $assignRolePermissions = static function (string $roleName, array $codes): void {
        $role = DB::table('roles')->where('name', $roleName)->first();
        if ($role === null || $codes === []) {
            return;
        }

        $permissions = DB::table('permissions')->whereIn('code', $codes)->get();

        foreach ($permissions as $permission) {
            DB::table('role_permissions')->updateOrInsert([
                'role_uuid' => $role->uuid,
                'permission_uuid' => $permission->uuid,
            ], [
                'uuid' => DB::table('role_permissions')
                    ->where('role_uuid', $role->uuid)
                    ->where('permission_uuid', $permission->uuid)
                    ->value('uuid') ?? Uuid::v4(),
                'role_uuid' => $role->uuid,
                'permission_uuid' => $permission->uuid,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    };

    $assignRolePermissions('Secretária/Recepção', [
        'schedule.view',
        'schedule.view_all',
        'schedule.create',
        'schedule.update',
        'schedule.cancel',
        'schedule.event_types.view',
        'attendance.view',
        'attendance.start_from_schedule',
        'attendance.substitute_professional',
        'patient_timeline.view',
    ]);

    $assignRolePermissions('Profissional clínico', [
        'schedule.view',
        'schedule.view_all',
        'schedule.update',
        'schedule.cancel',
        'attendance.view',
        'attendance.create',
        'attendance.update_own',
        'attendance.finalize',
        'attendance.start_from_schedule',
        'clinical_record.view_own',
        'clinical_record.create',
        'clinical_record.update_own',
        'clinical_record.finalize',
        'clinical_record.complement',
        'patient_timeline.view',
        'audio_record.create',
        'audio_record.view',
    ]);

    $assignRolePermissions('Auditor/leitura', [
        'schedule.view',
        'schedule.view_all',
    ]);
};
