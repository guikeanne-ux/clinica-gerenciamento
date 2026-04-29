<?php

declare(strict_types=1);

use Tests\Support\HttpTestClient;

it('returns 200 on healthcheck', function (): void {
    $response = HttpTestClient::get('/api/v1/health');

    expect($response['status'])->toBe(200);
});

it('returns standardized response format', function (): void {
    $response = HttpTestClient::get('/api/v1/health');
    $payload = json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKeys(['success', 'message', 'data', 'meta', 'errors']);
    expect($payload['success'])->toBeTrue();
    expect($payload['errors'])->toBeArray();
});
