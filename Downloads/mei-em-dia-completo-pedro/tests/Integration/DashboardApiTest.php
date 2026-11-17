<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class DashboardApiTest extends TestCase
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

    public function test_dashboard_api_requires_auth(): void
    {
        $request = new Request('GET', '/api/dashboard');
        $response = $this->router->dispatch('GET', '/api/dashboard', $request);

        self::assertSame(401, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($body['success']);
    }

    public function test_dashboard_api_returns_indicators_when_authenticated(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('GET', '/api/dashboard', ['month' => '11', 'year' => '2026']);
        $response = $this->router->dispatch('GET', '/api/dashboard', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('data', $body);
        $data = $body['data'];

        self::assertArrayHasKey('total_income', $data);
        self::assertArrayHasKey('total_expenses', $data);
        self::assertArrayHasKey('balance', $data);
        self::assertArrayHasKey('pending_income', $data);
        self::assertArrayHasKey('pending_expenses', $data);
        self::assertArrayHasKey('overdue_transactions', $data);

        self::assertEquals(3500.00, (float) $data['total_income']);
        self::assertEquals(270.00, (float) $data['total_expenses']);
        self::assertEquals(3230.00, (float) $data['balance']);
    }
}
