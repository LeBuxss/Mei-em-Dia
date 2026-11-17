<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class WebController
{
    public function index(Request $request): Response
    {
        $viewFile = dirname(__DIR__) . '/Views/index.php';

        if (!is_file($viewFile)) {
            return Response::html('<h1>MEI em Dia - Visualização não encontrada</h1>', 404);
        }

        ob_start();
        $appName = (string) ($_ENV['APP_NAME'] ?? 'MEI em Dia');
        $csrfToken = Session::token();
        require $viewFile;
        $html = (string) ob_get_clean();

        return Response::html($html);
    }
}
