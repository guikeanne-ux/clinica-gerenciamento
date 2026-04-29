<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('payment_tables', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('status')->default('active');
        $table->string('calculation_type');
        $table->date('effective_start_date');
        $table->date('effective_end_date')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    DB::schema()->create('payment_table_items', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('payment_table_uuid');
        $table->string('specialty')->nullable();
        $table->string('appointment_type')->nullable();
        $table->uuid('health_plan_uuid')->nullable();
        $table->string('procedure_code')->nullable();
        $table->decimal('fixed_value', 12, 2)->nullable();
        $table->decimal('percentage', 5, 2)->nullable();
        $table->unsignedInteger('duration_minutes')->nullable();
        $table->unsignedInteger('threshold_quantity')->nullable();
        $table->decimal('extra_value', 12, 2)->nullable();
        $table->longText('rules_json')->nullable();
        $table->date('effective_start_date');
        $table->date('effective_end_date')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    DB::schema()->create('professional_payment_configs', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('professional_uuid');
        $table->string('payment_mode');
        $table->uuid('payment_table_uuid')->nullable();
        $table->decimal('fixed_monthly_amount', 12, 2)->nullable();
        $table->decimal('fixed_per_attendance_amount', 12, 2)->nullable();
        $table->decimal('hybrid_base_amount', 12, 2)->nullable();
        $table->unsignedInteger('hybrid_threshold_quantity')->nullable();
        $table->decimal('hybrid_extra_amount_per_attendance', 12, 2)->nullable();
        $table->date('effective_start_date');
        $table->date('effective_end_date')->nullable();
        $table->string('status')->default('active');
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
};
