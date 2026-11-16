<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Core\Router;

return static function (Router $router): void {
    $healthController = new HealthController();

    $router->get('/api/health', [$healthController, 'show']);
};
