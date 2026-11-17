<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class ImprovementFeaturesTest extends TestCase
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

    public function test_export_csv_endpoint_generates_valid_csv(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('GET', '/api/transactions/export');
        $response = $this->router->dispatch('GET', '/api/transactions/export', $request);

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('text/csv', $response->headers()['Content-Type']);
        self::assertStringContainsString('attachment', $response->headers()['Content-Disposition']);

        $csvContent = $response->body();
        self::assertStringContainsString('Vencimento', $csvContent);
        self::assertStringContainsString('Descrição', $csvContent);
        self::assertStringContainsString('Valor (R$)', $csvContent);
        self::assertStringContainsString('Consultoria em TI', $csvContent);
    }

    public function test_annual_dashboard_report_calculates_dasn_limit(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $request = new Request('GET', '/api/dashboard/annual', ['year' => '2026']);
        $response = $this->router->dispatch('GET', '/api/dashboard/annual', $request);

        self::assertSame(200, $response->statusCode());
        $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($body['success']);
        $data = $body['data'];

        self::assertSame(2026, $data['year']);
        self::assertEquals(3500.00, (float) $data['total_annual_revenue']);
        self::assertSame(81000.00, (float) $data['annual_limit']);
        self::assertSame('normal', $data['status']);
        self::assertGreaterThan(0.0, (float) $data['remaining_limit']);
    }
}
