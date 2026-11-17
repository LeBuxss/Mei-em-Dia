<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\CsrfMiddleware;
use App\Services\TransactionService;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
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
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_sql_injection_defense_in_search_and_filters(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $maliciousPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE transactions; --",
            "' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12--",
            "1; WAITFOR DELAY '0:0:5'--",
        ];

        $txService = new TransactionService();

        foreach ($maliciousPayloads as $payload) {
            $result = $txService->list(1, ['search' => $payload]);
            self::assertTrue($result['success']);
            self::assertIsArray($result['data']['transactions']);
        }

        // Garante que a tabela transactions continua intacta
        $stmt = Database::getConnection()->query('SELECT COUNT(*) FROM transactions');
        self::assertGreaterThan(0, (int) $stmt->fetchColumn());
    }

    public function test_csrf_middleware_blocks_mutation_without_valid_token(): void
    {
        $middleware = new CsrfMiddleware();

        // 1. Requisição POST sem token CSRF
        $requestNoToken = new Request('POST', '/api/transactions', [], ['amount' => 100]);
        $response = $middleware->handle($requestNoToken);

        self::assertNotNull($response);
        self::assertSame(403, $response->statusCode());

        // 2. Requisição POST com token inválido
        $requestBadToken = new Request('POST', '/api/transactions', [], ['amount' => 100], ['x-csrf-token' => 'token_forjado']);
        $responseBad = $middleware->handle($requestBadToken);

        self::assertNotNull($responseBad);
        self::assertSame(403, $responseBad->statusCode());

        // 3. Requisição com token válido
        $validToken = Session::token();
        $requestValid = new Request('POST', '/api/transactions', [], ['amount' => 100], ['x-csrf-token' => $validToken]);
        $responseValid = $middleware->handle($requestValid);

        self::assertNull($responseValid, 'Token válido deve permitir passagem pelo middleware.');
    }

    public function test_idor_protection_prevents_cross_tenant_manipulation(): void
    {
        // Usuário do Business 2 tenta acessar recursos do Business 1
        Session::set('user_id', 999);
        Session::set('business_id', 999);

        // 1. Tenta acessar categoria 1 do Business 1
        $getCat = new Request('GET', '/api/categories/1');
        $resCat = $this->router->dispatch('GET', '/api/categories/1', $getCat);
        self::assertSame(404, $resCat->statusCode(), 'Não deve permitir acesso a categoria de outro negócio.');

        // 2. Tenta atualizar categoria 1 do Business 1
        $putCat = new Request('PUT', '/api/categories/1', [], ['name' => 'Hacked', 'type' => 'expense']);
        $resPutCat = $this->router->dispatch('PUT', '/api/categories/1', $putCat);
        self::assertSame(404, $resPutCat->statusCode(), 'Não deve permitir alteração em categoria de outro negócio.');

        // 3. Tenta acessar movimentação 1 do Business 1
        $getTx = new Request('GET', '/api/transactions/1');
        $resTx = $this->router->dispatch('GET', '/api/transactions/1', $getTx);
        self::assertSame(404, $resTx->statusCode(), 'Não deve permitir acesso a movimentação de outro negócio.');

        // 4. Tenta excluir movimentação 1 do Business 1
        $delTx = new Request('DELETE', '/api/transactions/1');
        $resDelTx = $this->router->dispatch('DELETE', '/api/transactions/1', $delTx);
        self::assertSame(404, $resDelTx->statusCode(), 'Não deve permitir exclusão de movimentação de outro negócio.');
    }

    public function test_passwords_are_never_exposed_in_plaintext_or_responses(): void
    {
        // 1. Rota de Login
        $reqLogin = new Request('POST', '/api/auth/login', [], [
            'email' => 'pedro@email.com',
            'password' => 'SenhaSegura123',
        ]);
        $resLogin = $this->router->dispatch('POST', '/api/auth/login', $reqLogin);
        $bodyLogin = $resLogin->body();

        self::assertStringNotContainsString('password_hash', $bodyLogin);
        self::assertStringNotContainsString('SenhaSegura123', $bodyLogin);

        // 2. Rota de Sessão /me
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $reqMe = new Request('GET', '/api/auth/me');
        $resMe = $this->router->dispatch('GET', '/api/auth/me', $reqMe);
        $bodyMe = $resMe->body();

        self::assertStringNotContainsString('password_hash', $bodyMe);
        self::assertStringNotContainsString('SenhaSegura123', $bodyMe);
    }
}
