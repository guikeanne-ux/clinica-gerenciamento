<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->create('companies', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('legal_name')->nullable();
        $table->string('trade_name')->nullable();
        $table->string('document')->nullable();
        $table->string('state_registration')->nullable();
        $table->string('municipal_registration')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('website')->nullable();
        $table->string('address_zipcode')->nullable();
        $table->string('address_street')->nullable();
        $table->string('address_number')->nullable();
        $table->string('address_complement')->nullable();
        $table->string('address_district')->nullable();
        $table->string('address_city')->nullable();
        $table->string('address_state')->nullable();
        $table->text('document_footer_text')->nullable();
        $table->longText('bank_data_json')->nullable();
        $table->longText('business_hours_json')->nullable();
        $table->string('timezone')->default('America/Sao_Paulo');
        $table->string('status')->default('active');
        $table->timestamps();
        $table->softDeletes();
    });

    DB::schema()->create('files', static function (Blueprint $table): void {
        $table->uuid('uuid')->primary();
        $table->string('original_name');
        $table->string('internal_name')->unique();
        $table->string('mime_type');
        $table->string('extension');
        $table->unsignedBigInteger('size_bytes');
        $table->string('checksum_hash');
        $table->binary('content_blob');
        $table->boolean('optimized')->default(false);
        $table->string('related_module')->nullable();
        $table->string('related_entity_type')->nullable();
        $table->uuid('related_entity_uuid')->nullable();
        $table->uuid('uploaded_by_user_uuid');
        $table->string('classification');
        $table->string('status')->default('active');
        $table->timestamps();
        $table->softDeletes();
    });
};
