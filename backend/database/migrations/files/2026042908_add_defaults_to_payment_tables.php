<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    if (!DB::schema()->hasColumn('payment_tables', 'default_percentage')) {
        DB::schema()->table('payment_tables', static function (Blueprint $table): void {
            $table->decimal('default_percentage', 5, 2)->nullable()->after('calculation_type');
        });
    }

    if (!DB::schema()->hasColumn('payment_tables', 'default_fixed_amount')) {
        DB::schema()->table('payment_tables', static function (Blueprint $table): void {
            $table->bigInteger('default_fixed_amount')->nullable()->after('default_percentage');
        });
    }
};

