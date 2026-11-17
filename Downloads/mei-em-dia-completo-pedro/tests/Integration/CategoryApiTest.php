<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class CategoryApiTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        Database::getConnection();

        $this->router = new Router();
        $registerRoutes = require dirname(__DIR__, 2) . '/routes/api.php';
        $registerRoutes($this->router);

        Session::start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec("DELETE FROM categories WHERE name LIKE 'API Cat%'");
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_categories_api_requires_auth(): void
    {
        $request = new Request('GET', '/api/categories');
        $response = $this->router->dispatch('GET', '/api/categories', $request);

        self::assertSame(401, $response->statusCode());
    }

    public function test_categories_api_index(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('GET', '/api/categories', ['type' => 'income']);
        $response = $this->router->dispatch('GET', '/api/categories', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('categories', $body['data']);
        self::assertGreaterThanOrEqual(2, count($body['data']['categories']));
    }

    public function test_categories_api_create_and_show(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        // 1. Criar
        $postReq = new Request('POST', '/api/categories', [], [
            'name' => 'API Cat Teste',
            'type' => 'expense',
        ]);
        $postRes = $this->router->dispatch('POST', '/api/categories', $postReq);

        self::assertSame(201, $postRes->statusCode());
        $postBody = json_decode($postRes->body(), true, 512, JSON_THROW_ON_ERROR);
        $catId = $postBody['data']['id'];

        // 2. Visualizar
        $getReq = new Request('GET', "/api/categories/{$catId}");
        $getRes = $this->router->dispatch('GET', "/api/categories/{$catId}", $getReq);

        self::assertSame(200, $getRes->statusCode());
        $getBody = json_decode($getRes->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('API Cat Teste', $getBody['data']['name']);
    }

    public function test_categories_api_delete_with_transactions_fails(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        // Categoria 1 (Vendas de Produtos) possui transações vinculadas no seed
        $request = new Request('DELETE', '/api/categories/1');
        $response = $this->router->dispatch('DELETE', '/api/categories/1', $request);

        self::assertSame(409, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($body['success']);
    }
}
