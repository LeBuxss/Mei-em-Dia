<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class BusinessApiTest extends TestCase
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
        $pdo->exec(
            "UPDATE businesses SET business_name = 'Pedro Tecnologia MEI', cnpj = '12345678000195' WHERE user_id = 1"
        );
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_business_api_requires_auth(): void
    {
        $request = new Request('GET', '/api/business');
        $response = $this->router->dispatch('GET', '/api/business', $request);

        self::assertSame(401, $response->statusCode());
    }

    public function test_business_api_show(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('GET', '/api/business');
        $response = $this->router->dispatch('GET', '/api/business', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertSame('Pedro Tecnologia MEI', $body['data']['business_name']);
    }

    public function test_business_api_update(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('PATCH', '/api/business', [], [
            'business_name' => 'Pedro Tecnologia Atualizada MEI',
            'cnpj' => '12345678000195',
        ]);
        $response = $this->router->dispatch('PATCH', '/api/business', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertSame('Pedro Tecnologia Atualizada MEI', $body['data']['business_name']);
    }

    public function test_business_api_update_fails_with_invalid_cnpj(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('PATCH', '/api/business', [], [
            'cnpj' => '1234',
        ]);
        $response = $this->router->dispatch('PATCH', '/api/business', $request);

        self::assertSame(422, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($body['success']);
        self::assertArrayHasKey('errors', $body);
    }
}
