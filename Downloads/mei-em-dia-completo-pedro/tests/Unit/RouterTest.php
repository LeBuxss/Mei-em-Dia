<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_it_dispatches_a_registered_get_route(): void
    {
        $router = new Router();
        $router->get('/api/health', static fn (): Response => Response::json([
            'success' => true,
        ]));

        $response = $router->dispatch('GET', '/api/health');

        self::assertSame(200, $response->statusCode());
        self::assertSame(
            ['success' => true],
            json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function test_it_returns_404_when_route_does_not_exist(): void
    {
        $router = new Router();

        $response = $router->dispatch('GET', '/api/missing');

        self::assertSame(404, $response->statusCode());
        self::assertSame(
            'Rota não encontrada.',
            json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR)['message']
        );
    }

    public function test_it_returns_405_when_method_is_not_allowed(): void
    {
        $router = new Router();
        $router->get('/api/health', static fn (): Response => Response::json([
            'success' => true,
        ]));

        $response = $router->dispatch('POST', '/api/health');

        self::assertSame(405, $response->statusCode());
        self::assertSame('GET', $response->headers()['Allow']);
    }

    public function test_it_normalizes_trailing_slashes(): void
    {
        $router = new Router();
        $router->get('/api/health', static fn (): Response => Response::json([
            'success' => true,
        ]));

        $response = $router->dispatch('GET', '/api/health/');

        self::assertSame(200, $response->statusCode());
    }
}
