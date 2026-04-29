<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Middleware;

use App\Core\Exceptions\HttpException;
use App\Core\Http\Request;
use App\Modules\Auth\Application\JwtService;
use App\Modules\Auth\Infrastructure\Models\User;
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
            throw new HttpException('Não autenticado.', 401);
        }

        $token = substr($header, 7);

        try {
            $decoded = $this->jwtService->decode($token);
            $user = User::query()->where('uuid', $decoded->sub)->first();

            if (! $user || $user->status !== 'active') {
                throw new HttpException('Não autenticado.', 401);
            }

            $request->setAttribute('auth_user', $user);

            return $user;
        } catch (Throwable) {
            throw new HttpException('Token inválido.', 401);
        }
    }
}
