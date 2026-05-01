<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

return static function (): void {
    $driver = (string) (DB::connection()->getConfig('driver') ?? '');
    if ($driver !== 'mysql') {
        return;
    }

    DB::statement('ALTER TABLE files MODIFY content_blob LONGBLOB NOT NULL');
};
