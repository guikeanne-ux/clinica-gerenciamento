<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('audit_logs', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('event');
        $table->uuid('user_uuid')->nullable();
        $table->longText('payload')->nullable();
        $table->timestamps();
    });

    DB::schema()->create('failed_login_attempts', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('login');
        $table->string('ip_address')->nullable();
        $table->timestamps();
    });
};
