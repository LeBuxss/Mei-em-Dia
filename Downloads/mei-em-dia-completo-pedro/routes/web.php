<?php

declare(strict_types=1);

use App\Controllers\WebController;
use App\Core\Router;

return static function (Router $router): void {
    $webController = new WebController();

    $router->get('/', [$webController, 'index']);
    $router->get('/login', [$webController, 'index']);
    $router->get('/register', [$webController, 'index']);
    $router->get('/dashboard', [$webController, 'index']);
    $router->get('/transactions', [$webController, 'index']);
    $router->get('/categories', [$webController, 'index']);
    $router->get('/business', [$webController, 'index']);
};
