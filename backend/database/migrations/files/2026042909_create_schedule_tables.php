<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('schedule_event_types', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('category');
        $table->string('color', 7)->nullable();
        $table->boolean('requires_patient')->default(false);
        $table->boolean('requires_professional')->default(false);
        $table->boolean('can_generate_attendance')->default(false);
        $table->boolean('can_generate_financial_entry')->default(false);
        $table->string('status')->default('ativo');
        $table->timestamps();
        $table->softDeletes();

        $table->index('category');
        $table->index('status');
    });

    DB::schema()->create('schedule_events', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('title');
        $table->text('description')->nullable();
        $table->uuid('event_type_uuid');
        $table->uuid('patient_uuid')->nullable();
        $table->uuid('professional_uuid')->nullable();
        $table->dateTime('starts_at');
        $table->dateTime('ends_at');
        $table->boolean('all_day')->default(false);
        $table->string('status')->default('agendado');
        $table->string('origin')->default('manual');
        $table->text('recurrence_rule')->nullable();
        $table->uuid('recurrence_group_uuid')->nullable();
        $table->string('room_or_location')->nullable();
        $table->string('color_override', 7)->nullable();
        $table->uuid('created_by_user_uuid')->nullable();
        $table->uuid('updated_by_user_uuid')->nullable();
        $table->dateTime('canceled_at')->nullable();
        $table->uuid('canceled_by_user_uuid')->nullable();
        $table->text('cancel_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index('event_type_uuid');
        $table->index('patient_uuid');
        $table->index('professional_uuid');
        $table->index('starts_at');
        $table->index('ends_at');
        $table->index('status');
        $table->index('origin');
        $table->index('recurrence_group_uuid');
    });
};
