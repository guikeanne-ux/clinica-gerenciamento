<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('attendances', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('patient_uuid');
        $table->uuid('professional_uuid');
        $table->uuid('schedule_event_uuid')->nullable();
        $table->dateTime('starts_at')->nullable();
        $table->dateTime('ends_at')->nullable();
        $table->unsignedInteger('duration_minutes')->nullable();
        $table->string('status')->default('rascunho');
        $table->string('attendance_type')->default('consulta');
        $table->string('modality')->default('presencial');
        $table->longText('financial_table_snapshot_json')->nullable();
        $table->bigInteger('calculated_payout_value')->nullable();
        $table->text('internal_notes')->nullable();
        $table->uuid('created_by_user_uuid')->nullable();
        $table->uuid('updated_by_user_uuid')->nullable();
        $table->dateTime('finalized_at')->nullable();
        $table->uuid('finalized_by_user_uuid')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->unique('schedule_event_uuid');
        $table->index('patient_uuid');
        $table->index('professional_uuid');
        $table->index('status');
    });

    DB::schema()->create('clinical_records', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('patient_uuid');
        $table->uuid('professional_uuid');
        $table->uuid('attendance_uuid');
        $table->string('record_type');
        $table->string('title');
        $table->longText('content_markdown')->nullable();
        $table->string('status')->default('rascunho');
        $table->unsignedInteger('version')->default(1);
        $table->uuid('created_by_user_uuid');
        $table->dateTime('finalized_at')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index('attendance_uuid');
        $table->index('patient_uuid');
        $table->index('professional_uuid');
        $table->index('record_type');
        $table->index('status');
    });

    DB::schema()->create('audio_records', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('patient_uuid');
        $table->uuid('professional_uuid');
        $table->uuid('attendance_uuid');
        $table->string('title');
        $table->uuid('file_uuid');
        $table->unsignedInteger('duration_seconds')->nullable();
        $table->string('status')->default('ativo');
        $table->timestamps();
        $table->softDeletes();

        $table->index('attendance_uuid');
        $table->index('patient_uuid');
        $table->index('professional_uuid');
        $table->index('file_uuid');
    });
};
