<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('roles', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('name')->unique();
        $table->timestamps();
    });

    DB::schema()->create('permissions', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('code')->unique();
        $table->timestamps();
    });

    DB::schema()->create('role_permissions', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('role_uuid');
        $table->uuid('permission_uuid');
        $table->timestamps();
        $table->unique(['role_uuid', 'permission_uuid']);
    });

    DB::schema()->create('user_roles', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('user_uuid');
        $table->uuid('role_uuid');
        $table->timestamps();
        $table->unique(['user_uuid', 'role_uuid']);
    });

    DB::schema()->create('user_permission_overrides', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('user_uuid');
        $table->uuid('permission_uuid');
        $table->boolean('is_allowed');
        $table->timestamps();
        $table->unique(['user_uuid', 'permission_uuid']);
    });
};
