<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('specialties', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('name')->unique();
        $table->string('status')->default('active');
        $table->timestamps();
    });
};
