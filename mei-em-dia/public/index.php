<?php

declare(strict_types=1);

use App\Core\Router;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$timezone = $_ENV['APP_TIMEZONE'] ?? 'America/Fortaleza';
date_default_timezone_set($timezone);

$router = new Router();
$registerRoutes = require dirname(__DIR__) . '/routes/api.php';
$registerRoutes($router);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

try {
    $response = $router->dispatch($method, $path);
    $response->send();
} catch (Throwable $exception) {
    error_log($exception->__toString());

    $debug = filter_var(
        $_ENV['APP_DEBUG'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

    $payload = [
        'success' => false,
        'message' => 'Ocorreu um erro interno.',
    ];

    if ($debug) {
        $payload['debug'] = [
            'type' => $exception::class,
            'message' => $exception->getMessage(),
        ];
    }

    \App\Core\Response::json($payload, 500)->send();
}
