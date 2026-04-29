<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('users', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('name');
        $table->string('login')->unique();
        $table->string('email')->unique();
        $table->string('password_hash');
        $table->string('status');
        $table->timestamp('last_access_at')->nullable();
        $table->uuid('person_uuid')->nullable();
        $table->uuid('professional_uuid')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
};
