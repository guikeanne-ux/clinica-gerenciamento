<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

$files = glob(__DIR__ . '/files/*.php') ?: [];
sort($files);

foreach ($files as $file) {
    $migration = require $file;
    try {
        $migration();
        echo 'OK ' . basename($file) . PHP_EOL;
    } catch (Throwable $throwable) {
        echo 'SKIP ' . basename($file) . ' (' . $throwable->getMessage() . ')' . PHP_EOL;
    }
}
