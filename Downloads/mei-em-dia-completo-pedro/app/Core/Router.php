<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var list<array{
     *     method: string,
     *     path: string,
     *     regex: string,
     *     paramNames: list<string>,
     *     handler: callable,
     *     middlewares: list<callable|string>
     * }>
     */
    private array $routes = [];

    /**
     * @param callable $handler
     * @param list<callable|string> $middlewares
     */
    public function get(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    /**
     * @param callable $handler
     * @param list<callable|string> $middlewares
     */
    public function post(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    /**
     * @param callable $handler
     * @param list<callable|string> $middlewares
     */
    public function put(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    /**
     * @param callable $handler
     * @param list<callable|string> $middlewares
     */
    public function patch(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('PATCH', $path, $handler, $middlewares);
    }

    /**
     * @param callable $handler
     * @param list<callable|string> $middlewares
     */
    public function delete(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    /**
     * @param callable $handler
     * @param list<callable|string> $middlewares
     */
    public function add(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', static function (array $matches) use (&$paramNames): string {
            $paramNames[] = $matches[1];

            return '([^/]+)';
        }, $normalizedPath);

        $regex = '#^' . ($regex ?? $normalizedPath) . '$#';

        $this->routes[] = [
            'method' => $normalizedMethod,
            'path' => $normalizedPath,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $method, string $path, ?Request $request = null): Response
    {
        $normalizedPath = $this->normalizePath($path);
        $normalizedMethod = strtoupper($method);

        $matchingPathRoutes = [];
        $matchedRoute = null;
        $matchedParams = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $normalizedPath, $matches)) {
                $matchingPathRoutes[$route['method']] = true;

                if ($route['method'] === $normalizedMethod) {
                    $matchedRoute = $route;
                    array_shift($matches);
                    $params = [];
                    foreach ($route['paramNames'] as $index => $paramName) {
                        $params[$paramName] = $matches[$index] ?? '';
                    }
                    $matchedParams = $params;
                    break;
                }
            }
        }

        if ($matchedRoute === null) {
            if ($matchingPathRoutes !== []) {
                $allowedMethods = array_keys($matchingPathRoutes);

                return Response::json([
                    'success' => false,
                    'message' => 'Método não permitido.',
                ], 405, ['Allow' => implode(', ', $allowedMethods)]);
            }

            return Response::json([
                'success' => false,
                'message' => 'Rota não encontrada.',
            ], 404);
        }

        $requestInstance = $request ?? new Request($normalizedMethod, $normalizedPath);
        foreach ($matchedParams as $paramKey => $paramValue) {
            $requestInstance->setAttribute($paramKey, $paramValue);
        }

        // Execução de middlewares
        foreach ($matchedRoute['middlewares'] as $middleware) {
            $middlewareResponse = $this->executeMiddleware($middleware, $requestInstance);
            if ($middlewareResponse instanceof Response) {
                return $middlewareResponse;
            }
        }

        $handler = $matchedRoute['handler'];

        /** @var Response */
        return $handler($requestInstance, ...array_values($matchedParams));
    }

    private function executeMiddleware(callable|string $middleware, Request $request): ?Response
    {
        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                /** @var ?Response */
                return $instance->handle($request);
            }
        }

        if (is_callable($middleware)) {
            /** @var ?Response */
            return $middleware($request);
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');

        return $normalized === '/' ? '/' : $normalized;
    }
}
