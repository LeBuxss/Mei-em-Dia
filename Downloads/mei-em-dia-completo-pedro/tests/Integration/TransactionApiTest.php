<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class TransactionApiTest extends TestCase
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
        $pdo->exec("DELETE FROM transactions WHERE description LIKE 'API Tx%'");
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_transactions_api_requires_auth(): void
    {
        $request = new Request('GET', '/api/transactions');
        $response = $this->router->dispatch('GET', '/api/transactions', $request);

        self::assertSame(401, $response->statusCode());
    }

    public function test_transactions_api_index_with_filters_and_pagination(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('GET', '/api/transactions', [
            'type' => 'income',
            'status' => 'paid',
            'page' => '1',
            'limit' => '5',
        ]);
        $response = $this->router->dispatch('GET', '/api/transactions', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('transactions', $body['data']);
        self::assertArrayHasKey('pagination', $body['data']);
        self::assertSame(1, $body['data']['pagination']['page']);
        self::assertSame(5, $body['data']['pagination']['limit']);
    }

    public function test_transactions_api_create_and_show(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        // 1. Criar
        $postReq = new Request('POST', '/api/transactions', [], [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'API Tx Nova Venda',
            'amount' => 890.00,
            'due_date' => '2026-11-25',
            'status' => 'pending',
        ]);
        $postRes = $this->router->dispatch('POST', '/api/transactions', $postReq);

        self::assertSame(201, $postRes->statusCode());
        $postBody = json_decode($postRes->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($postBody['success']);
        $txId = (int) $postBody['data']['id'];

        // 2. Buscar
        $getReq = new Request('GET', "/api/transactions/{$txId}");
        $getRes = $this->router->dispatch('GET', "/api/transactions/{$txId}", $getReq);

        self::assertSame(200, $getRes->statusCode());
        $getBody = json_decode($getRes->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('API Tx Nova Venda', $getBody['data']['description']);
    }

    public function test_transactions_api_patch_status_and_delete(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        // Cria
        $postReq = new Request('POST', '/api/transactions', [], [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'API Tx Status Patch',
            'amount' => 450.00,
            'due_date' => '2026-11-22',
            'status' => 'pending',
        ]);
        $postRes = $this->router->dispatch('POST', '/api/transactions', $postReq);
        $tx = json_decode($postRes->body(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $txId = (int) $tx['id'];
        $version = (int) $tx['version'];

        // Atualiza status para 'paid'
        $patchReq = new Request('PATCH', "/api/transactions/{$txId}/status", [], [
            'status' => 'paid',
            'version' => $version,
        ]);
        $patchRes = $this->router->dispatch('PATCH', "/api/transactions/{$txId}/status", $patchReq);

        self::assertSame(200, $patchRes->statusCode());
        $patchBody = json_decode($patchRes->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('paid', $patchBody['data']['status']);
        self::assertNotNull($patchBody['data']['paid_at']);
        self::assertSame($version + 1, (int) $patchBody['data']['version']);

        // Exclui
        $delReq = new Request('DELETE', "/api/transactions/{$txId}");
        $delRes = $this->router->dispatch('DELETE', "/api/transactions/{$txId}", $delReq);

        self::assertSame(200, $delRes->statusCode());
    }

    public function test_transactions_api_isolation_returns_404_for_other_business(): void
    {
        // Define sessão para outro negócio (ex: 888)
        Session::set('user_id', 888);
        Session::set('business_id', 888);

        // Transação 1 é do business 1
        $req = new Request('GET', '/api/transactions/1');
        $res = $this->router->dispatch('GET', '/api/transactions/1', $req);

        self::assertSame(404, $res->statusCode());
    }
}
