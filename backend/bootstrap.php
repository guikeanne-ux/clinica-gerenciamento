<?php

declare(strict_types=1);

use App\Core\Database\DatabaseManager;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/.env')) {
    Dotenv::createImmutable($rootPath)->safeLoad();
}

DatabaseManager::boot($_ENV);
