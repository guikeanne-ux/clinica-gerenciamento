<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('patients', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('full_name');
        $table->string('social_name')->nullable();
        $table->date('birth_date');
        $table->string('gender')->nullable();
        $table->string('cpf')->nullable()->unique();
        $table->string('rg')->nullable();
        $table->string('cns')->nullable();
        $table->string('cid')->nullable();
        $table->string('health_plan_card_number')->nullable();
        $table->string('health_plan_name')->nullable();
        $table->string('email')->nullable();
        $table->string('father_name')->nullable();
        $table->string('mother_name')->nullable();
        $table->string('phone_primary')->nullable();
        $table->string('phone_secondary')->nullable();
        $table->string('address_zipcode')->nullable();
        $table->string('address_street')->nullable();
        $table->string('address_number')->nullable();
        $table->string('address_complement')->nullable();
        $table->string('address_district')->nullable();
        $table->string('address_city')->nullable();
        $table->string('address_state')->nullable();
        $table->text('general_notes')->nullable();
        $table->string('status')->default('active');
        $table->string('origin')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    DB::schema()->create('patient_responsibles', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->uuid('patient_uuid');
        $table->string('name');
        $table->string('kinship')->nullable();
        $table->string('cpf')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('address_zipcode')->nullable();
        $table->string('address_street')->nullable();
        $table->string('address_number')->nullable();
        $table->string('address_complement')->nullable();
        $table->string('address_district')->nullable();
        $table->string('address_city')->nullable();
        $table->string('address_state')->nullable();
        $table->text('notes')->nullable();
        $table->boolean('is_financial_responsible')->default(false);
        $table->boolean('is_primary_contact')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    DB::schema()->create('professionals', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('full_name');
        $table->string('cpf')->nullable()->unique();
        $table->string('professional_registry')->nullable();
        $table->string('registry_state')->nullable();
        $table->string('main_specialty')->nullable();
        $table->longText('secondary_specialties_json')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable()->unique();
        $table->string('address_zipcode')->nullable();
        $table->string('address_street')->nullable();
        $table->string('address_number')->nullable();
        $table->string('address_complement')->nullable();
        $table->string('address_district')->nullable();
        $table->string('address_city')->nullable();
        $table->string('address_state')->nullable();
        $table->date('entry_date')->nullable();
        $table->string('status')->default('active');
        $table->string('contract_type')->nullable();
        $table->longText('bank_data_json')->nullable();
        $table->string('schedule_color')->nullable();
        $table->longText('availability_config_json')->nullable();
        $table->uuid('user_uuid')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    DB::schema()->create('suppliers', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('name_or_legal_name');
        $table->string('document')->nullable()->unique();
        $table->string('contact_name')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('address_zipcode')->nullable();
        $table->string('address_street')->nullable();
        $table->string('address_number')->nullable();
        $table->string('address_complement')->nullable();
        $table->string('address_district')->nullable();
        $table->string('address_city')->nullable();
        $table->string('address_state')->nullable();
        $table->string('category')->nullable();
        $table->text('notes')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
        $table->softDeletes();
    });
};
