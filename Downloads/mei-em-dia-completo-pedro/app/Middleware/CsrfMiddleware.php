<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class CsrfMiddleware
{
    public function handle(Request $request): ?Response
    {
        $method = $request->method();

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        $token = $request->header('x-csrf-token')
            ?? (string) $request->get('_csrf_token', '');

        if (!Session::validateToken($token)) {
            return Response::json([
                'success' => false,
                'message' => 'Token CSRF inválido ou ausente.',
            ], 403);
        }

        return null;
    }
}
