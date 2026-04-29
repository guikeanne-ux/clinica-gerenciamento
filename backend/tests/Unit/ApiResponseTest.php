<?php

declare(strict_types=1);

use App\Core\Support\ApiResponse;

it('builds standardized success response', function (): void {
    $payload = ApiResponse::success(data: ['key' => 'value']);

    expect($payload)->toHaveKeys(['success', 'message', 'data', 'meta', 'errors']);
    expect($payload['success'])->toBeTrue();
    expect($payload['data'])->toBe(['key' => 'value']);
});

it('builds standardized error response', function (): void {
    $payload = ApiResponse::error('Erro de validação.', [['field' => 'email', 'message' => 'E-mail inválido.']]);

    expect($payload)->toHaveKeys(['success', 'message', 'data', 'meta', 'errors']);
    expect($payload['success'])->toBeFalse();
    expect($payload['data'])->toBeNull();
});
