<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\BusinessService;

final class BusinessController
{
    public function __construct(
        private ?BusinessService $businessService = null
    ) {
        $this->businessService = $businessService ?? new BusinessService();
    }

    public function show(Request $request): Response
    {
        $userId = (int) ($request->getAttribute('auth_user_id') ?? Session::get('user_id'));

        $result = $this->businessService->get($userId);

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if (isset($result['data'])) {
            $payload['data'] = $result['data'];
        }

        return Response::json($payload, $result['status']);
    }

    public function update(Request $request): Response
    {
        $userId = (int) ($request->getAttribute('auth_user_id') ?? Session::get('user_id'));

        $result = $this->businessService->update($userId, $request->body());

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
}
