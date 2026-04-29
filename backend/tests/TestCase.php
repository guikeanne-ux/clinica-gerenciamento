<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database\DatabaseManager;
use App\Core\Http\Kernel;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['JWT_SECRET'] = 'test-secret';
        $_ENV['JWT_TTL'] = '3600';

        DatabaseManager::reset();
        require dirname(__DIR__) . '/bootstrap.php';
        require dirname(__DIR__) . '/database/migrations/run.php';
        require dirname(__DIR__) . '/database/seeders/run.php';
    }

    protected function request(string $method, string $uri, array $body = [], array $headers = []): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        return (new Kernel())->handle($method, $uri, $normalized, $body);
    }

    protected function get(string $uri, array $headers = []): array
    {
        return $this->request('GET', $uri, [], $headers);
    }

    protected function post(string $uri, array $body = [], array $headers = []): array
    {
        return $this->request('POST', $uri, $body, $headers);
    }
}
