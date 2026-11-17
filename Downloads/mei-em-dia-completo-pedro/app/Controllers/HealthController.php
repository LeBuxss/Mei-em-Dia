<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

final class HealthController
{
    public function show(): Response
    {
        return Response::json([
            'success' => true,
            'message' => 'MEI em Dia está funcionando.',
            'data' => [
                'application' => 'MEI em Dia',
                'status' => 'ok',
            ],
        ]);
    }
}
