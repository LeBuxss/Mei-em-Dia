<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BusinessController;
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\TransactionController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return static function (Router $router): void {
    $healthController = new HealthController();
    $authController = new AuthController();
    $businessController = new BusinessController();
    $categoryController = new CategoryController();
    $transactionController = new TransactionController();
    $dashboardController = new DashboardController();

    // Rota pública de status da aplicação
    $router->get('/api/health', [$healthController, 'show']);

    // Rotas públicas de Autenticação
    $router->get('/api/auth/csrf', [$authController, 'csrf']);
    $router->post('/api/auth/register', [$authController, 'register']);
    $router->post('/api/auth/login', [$authController, 'login']);

    // Rotas autenticadas de Autenticação
    $router->get('/api/auth/me', [$authController, 'me'], [AuthMiddleware::class]);
    $router->post('/api/auth/logout', [$authController, 'logout'], [AuthMiddleware::class]);

    // Rotas do Perfil Empresarial
    $router->get('/api/business', [$businessController, 'show'], [AuthMiddleware::class]);
    $router->patch('/api/business', [$businessController, 'update'], [AuthMiddleware::class]);

    // Rotas de Categorias
    $router->get('/api/categories', [$categoryController, 'index'], [AuthMiddleware::class]);
    $router->post('/api/categories', [$categoryController, 'store'], [AuthMiddleware::class]);
    $router->get('/api/categories/{id}', [$categoryController, 'show'], [AuthMiddleware::class]);
    $router->put('/api/categories/{id}', [$categoryController, 'update'], [AuthMiddleware::class]);
    $router->delete('/api/categories/{id}', [$categoryController, 'destroy'], [AuthMiddleware::class]);

    // Rotas de Movimentações Financeiras
    $router->get('/api/transactions/export', [$transactionController, 'export'], [AuthMiddleware::class]);
    $router->get('/api/transactions', [$transactionController, 'index'], [AuthMiddleware::class]);
    $router->post('/api/transactions', [$transactionController, 'store'], [AuthMiddleware::class]);
    $router->get('/api/transactions/{id}', [$transactionController, 'show'], [AuthMiddleware::class]);
    $router->put('/api/transactions/{id}', [$transactionController, 'update'], [AuthMiddleware::class]);
    $router->patch('/api/transactions/{id}/status', [$transactionController, 'updateStatus'], [AuthMiddleware::class]);
    $router->delete('/api/transactions/{id}', [$transactionController, 'destroy'], [AuthMiddleware::class]);

    // Rotas do Dashboard Financeiro
    $router->get('/api/dashboard/annual', [$dashboardController, 'annual'], [AuthMiddleware::class]);
    $router->get('/api/dashboard', [$dashboardController, 'index'], [AuthMiddleware::class]);
};
