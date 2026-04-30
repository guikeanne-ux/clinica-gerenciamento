<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Middleware;

use App\Core\Exceptions\AuthenticationException;
use App\Core\Exceptions\ErrorCode;
use App\Core\Http\Request;
use App\Modules\Auth\Application\JwtService;
use App\Modules\Auth\Infrastructure\Models\User;
use Firebase\JWT\ExpiredException;
use Throwable;

final class AuthMiddleware
{
    public function __construct(private readonly JwtService $jwtService = new JwtService())
    {
    }

    public function handle(Request $request): User
    {
        $header = $request->header('authorization');
        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            throw new AuthenticationException('Não autenticado.', ErrorCode::UNAUTHORIZED);
        }

        $token = substr($header, 7);

        try {
            $decoded = $this->jwtService->decode($token);
            $user = User::query()->where('uuid', $decoded->sub)->first();

            if (! $user || $user->status !== 'active') {
                throw new AuthenticationException('Não autenticado.', ErrorCode::UNAUTHORIZED);
            }

            $request->setAttribute('auth_user', $user);

            return $user;
        } catch (ExpiredException) {
            throw new AuthenticationException('Sua sessão expirou. Faça login novamente.', ErrorCode::TOKEN_EXPIRED);
        } catch (Throwable) {
            throw new AuthenticationException('Token inválido.', ErrorCode::UNAUTHORIZED);
        }
    }
}
