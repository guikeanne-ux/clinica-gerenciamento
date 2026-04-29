<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $headers = [],
        public readonly array $body = [],
        public readonly array $query = [],
        public array $attributes = []
    ) {
    }

    public function header(string $name): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? null;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
}
