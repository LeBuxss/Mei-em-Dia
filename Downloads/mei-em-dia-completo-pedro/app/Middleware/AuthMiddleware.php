<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        $userId = Session::get('user_id');

        if ($userId === null) {
            return Response::json([
                'success' => false,
                'message' => 'Acesso não autorizado. Faça login para continuar.',
            ], 401);
        }

        $request->setAttribute('auth_user_id', (int) $userId);
        $request->setAttribute('auth_business_id', (int) Session::get('business_id', 0));

        return null;
    }
}
