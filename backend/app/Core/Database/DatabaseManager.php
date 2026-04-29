<?php

declare(strict_types=1);

namespace App\Core\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

final class DatabaseManager
{
    private static ?Capsule $capsule = null;

    public static function boot(array $config): Capsule
    {
        if (self::$capsule instanceof Capsule) {
            return self::$capsule;
        }

        $capsule = new Capsule();
        $driver = $config['DB_CONNECTION'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $capsule->addConnection([
                'driver' => 'sqlite',
                'database' => $config['DB_DATABASE'] ?? ':memory:',
                'prefix' => '',
            ]);
        } else {
            $capsule->addConnection([
                'driver' => $driver,
                'host' => $config['DB_HOST'] ?? 'db',
                'port' => (int) ($config['DB_PORT'] ?? 3306),
                'database' => $config['DB_DATABASE'] ?? '',
                'username' => $config['DB_USERNAME'] ?? '',
                'password' => $config['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);
        }

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        self::$capsule = $capsule;

        return $capsule;
    }

    public static function reset(): void
    {
        self::$capsule = null;
    }
}
