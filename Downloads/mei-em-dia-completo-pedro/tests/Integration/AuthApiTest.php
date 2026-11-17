<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class AuthApiTest extends TestCase
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
        $pdo->exec("DELETE FROM users WHERE email IN ('api_user@email.com')");
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_api_csrf_endpoint_returns_token(): void
    {
        $request = new Request('GET', '/api/auth/csrf');
        $response = $this->router->dispatch('GET', '/api/auth/csrf', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertNotEmpty($body['data']['csrf_token']);
    }

    public function test_api_register_endpoint(): void
    {
        $request = new Request('POST', '/api/auth/register', [], [
            'name' => 'API User',
            'email' => 'api_user@email.com',
            'password' => 'SenhaSegura123',
            'business_name' => 'Negócio da API',
        ]);

        $response = $this->router->dispatch('POST', '/api/auth/register', $request);

        self::assertSame(201, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertSame('API User', $body['data']['user']['name']);
        self::assertSame('Negócio da API', $body['data']['business']['business_name']);
    }

    public function test_api_login_endpoint_success(): void
    {
        $request = new Request('POST', '/api/auth/login', [], [
            'email' => 'pedro@email.com',
            'password' => 'SenhaSegura123',
        ]);

        $response = $this->router->dispatch('POST', '/api/auth/login', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertSame('pedro@email.com', $body['data']['user']['email']);
    }

    public function test_api_login_endpoint_unauthorized(): void
    {
        $request = new Request('POST', '/api/auth/login', [], [
            'email' => 'pedro@email.com',
            'password' => 'SenhaInvalida999',
        ]);

        $response = $this->router->dispatch('POST', '/api/auth/login', $request);

        self::assertSame(401, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($body['success']);
        self::assertSame('Credenciais inválidas.', $body['message']);
    }

    public function test_api_me_endpoint_requires_authentication(): void
    {
        // Sem sessão iniciada
        $request = new Request('GET', '/api/auth/me');
        $response = $this->router->dispatch('GET', '/api/auth/me', $request);

        self::assertSame(401, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($body['success']);
        self::assertSame('Acesso não autorizado. Faça login para continuar.', $body['message']);
    }

    public function test_api_me_endpoint_returns_user_when_authenticated(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('GET', '/api/auth/me');
        $response = $this->router->dispatch('GET', '/api/auth/me', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertSame(1, $body['data']['user']['id']);
        self::assertSame('pedro@email.com', $body['data']['user']['email']);
        self::assertSame('Pedro Tecnologia MEI', $body['data']['business']['business_name']);
    }

    public function test_api_logout_endpoint(): void
    {
        Session::set('user_id', 1);

        $request = new Request('POST', '/api/auth/logout');
        $response = $this->router->dispatch('POST', '/api/auth/logout', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertNull(Session::get('user_id'));
    }
}
