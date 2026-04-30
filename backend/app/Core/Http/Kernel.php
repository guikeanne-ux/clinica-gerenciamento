<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Exceptions\ErrorHandler;
use App\Core\Exceptions\HttpException;
use App\Core\Support\Uuid;
use LogicException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

final class Kernel
{
    public function handle(string $method, string $uri, array $headers = [], array $body = []): array
    {
        $requestId = Uuid::v4();

        try {
            $routes = require __DIR__ . '/../../../routes/api.php';
            $context = new RequestContext('/');
            $context->setMethod($method);
            $matcher = new UrlMatcher($routes, $context);

            $path = parse_url($uri, PHP_URL_PATH) ?: '/';
            $queryString = parse_url($uri, PHP_URL_QUERY) ?: '';
            parse_str($queryString, $query);

            $route = $matcher->match($path);
            $action = $route['_controller'] ?? null;

            if (! is_callable($action)) {
                throw new LogicException('Controller da rota não é executável.');
            }

            unset($route['_controller'], $route['_route']);
            $request = new Request($method, $path, $headers, $body, $query, $route);
            $request->setAttribute('request_id', $requestId);
            $result = $action($request);

            if (! is_array($result) || ! isset($result['status'], $result['body'], $result['headers'])) {
                throw new LogicException('Controller deve retornar estrutura de response válida.');
            }

            return $result;
        } catch (ResourceNotFoundException) {
            return JsonResponse::make(
                ErrorHandler::handle(new HttpException('Rota não encontrada.', 404, [], 'NOT_FOUND'), $requestId)['body'],
                404
            );
        } catch (\Throwable $throwable) {
            $error = ErrorHandler::handle($throwable, $requestId);
            return JsonResponse::make($error['body'], $error['status']);
        }
    }
}
