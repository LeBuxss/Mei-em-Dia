<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$timezone = $_ENV['APP_TIMEZONE'] ?? 'America/Fortaleza';
date_default_timezone_set($timezone);

Session::start();

$router = new Router();
$registerRoutes = require dirname(__DIR__) . '/routes/api.php';
$registerRoutes($router);

$webRoutesFile = dirname(__DIR__) . '/routes/web.php';
if (file_exists($webRoutesFile)) {
    $registerWebRoutes = require $webRoutesFile;
    $registerWebRoutes($router);
}

$request = Request::createFromGlobals();

try {
    $response = $router->dispatch($request->method(), $request->path(), $request);
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

    Response::json($payload, 500)->send();
}
