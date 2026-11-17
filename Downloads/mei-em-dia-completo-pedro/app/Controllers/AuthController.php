<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

final class AuthController
{
    public function __construct(
        private ?AuthService $authService = null
    ) {
        $this->authService = $authService ?? new AuthService();
    }

    public function register(Request $request): Response
    {
        $result = $this->authService->register($request->body());

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if (isset($result['data'])) {
            $payload['data'] = $result['data'];
        }

        if (isset($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        return Response::json($payload, $result['status']);
    }

    public function login(Request $request): Response
    {
        $result = $this->authService->login($request->body());

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if (isset($result['data'])) {
            $payload['data'] = $result['data'];
        }

        if (isset($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        return Response::json($payload, $result['status']);
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();

        return Response::json([
            'success' => true,
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }

    public function me(Request $request): Response
    {
        $data = $this->authService->me();

        if ($data === null) {
            return Response::json([
                'success' => false,
                'message' => 'Acesso não autorizado.',
            ], 401);
        }

        return Response::json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function csrf(Request $request): Response
    {
        return Response::json([
            'success' => true,
            'data' => [
                'csrf_token' => Session::token(),
            ],
        ]);
    }
}
