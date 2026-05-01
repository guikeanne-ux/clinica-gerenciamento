<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->table('attendances', static function (Blueprint $table): void {
        if (!DB::schema()->hasColumn('attendances', 'original_professional_uuid')) {
            $table->uuid('original_professional_uuid')->nullable()->after('professional_uuid');
            $table->uuid('substituted_professional_uuid')->nullable()->after('original_professional_uuid');
            $table->text('substitution_reason')->nullable()->after('substituted_professional_uuid');
            $table->dateTime('substituted_at')->nullable()->after('substitution_reason');
            $table->uuid('substituted_by_user_uuid')->nullable()->after('substituted_at');
            $table->index('original_professional_uuid');
            $table->index('substituted_professional_uuid');
        }
    });
};
