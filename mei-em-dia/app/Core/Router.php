<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string, array<string, callable(): Response>>
     */
    private array $routes = [];

    /**
     * @param callable(): Response $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param callable(): Response $handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param callable(): Response $handler
     */
    public function add(string $method, string $path, callable $handler): void
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        $this->routes[$normalizedPath][$normalizedMethod] = $handler;
    }

    public function dispatch(string $method, string $path): Response
    {
        $normalizedPath = $this->normalizePath($path);
        $normalizedMethod = strtoupper($method);

        if (!isset($this->routes[$normalizedPath])) {
            return Response::json([
                'success' => false,
                'message' => 'Rota não encontrada.',
            ], 404);
        }

        if (!isset($this->routes[$normalizedPath][$normalizedMethod])) {
            $allowedMethods = array_keys($this->routes[$normalizedPath]);

            return Response::json([
                'success' => false,
                'message' => 'Método não permitido.',
            ], 405, ['Allow' => implode(', ', $allowedMethods)]);
        }

        return ($this->routes[$normalizedPath][$normalizedMethod])();
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');

        return $normalized === '/' ? '/' : $normalized;
    }
}
