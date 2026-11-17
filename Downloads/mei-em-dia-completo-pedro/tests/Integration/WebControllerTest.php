<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class WebControllerTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();

        $registerApi = require dirname(__DIR__, 2) . '/routes/api.php';
        $registerApi($this->router);

        $registerWeb = require dirname(__DIR__, 2) . '/routes/web.php';
        $registerWeb($this->router);

        Session::start();
    }

    public function test_index_page_returns_200_and_renders_html(): void
    {
        $request = new Request('GET', '/');
        $response = $this->router->dispatch('GET', '/', $request);

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('text/html', $response->headers()['Content-Type']);

        $body = $response->body();
        self::assertStringContainsString('MEI em Dia', $body);
        self::assertStringContainsString('authSection', $body);
        self::assertStringContainsString('appSection', $body);
        self::assertStringContainsString('formLogin', $body);
        self::assertStringContainsString('formRegister', $body);
        self::assertStringContainsString('chartIncomeExpense', $body);
        self::assertStringContainsString('modalTransaction', $body);
    }

    public function test_web_routes_render_spa_entrypoint(): void
    {
        foreach (['/dashboard', '/transactions', '/categories', '/business'] as $path) {
            $request = new Request('GET', $path);
            $response = $this->router->dispatch('GET', $path, $request);

            self::assertSame(200, $response->statusCode());
            self::assertStringContainsString('MEI em Dia', $response->body());
        }
    }
}
