<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

require dirname(__DIR__, 2) . '/bootstrap.php';

if (! DB::schema()->hasTable('roles')) {
    require dirname(__DIR__) . '/migrations/run.php';
}

$files = glob(__DIR__ . '/files/*.php') ?: [];
sort($files);

foreach ($files as $file) {
    $seeder = require $file;
    $seeder();
    echo 'OK ' . basename($file) . PHP_EOL;
}
