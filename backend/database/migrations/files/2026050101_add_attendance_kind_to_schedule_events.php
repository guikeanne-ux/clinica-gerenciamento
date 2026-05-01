<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return static function (): void {
    DB::schema()->table('schedule_events', static function (Blueprint $table): void {
        if (! DB::schema()->hasColumn('schedule_events', 'is_attendance')) {
            $table->boolean('is_attendance')->default(false)->after('event_type_uuid');
            $table->index('is_attendance');
        }
    });
};

