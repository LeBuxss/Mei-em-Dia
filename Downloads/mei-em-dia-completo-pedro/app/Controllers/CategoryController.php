<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CategoryService;

final class CategoryController
{
    public function __construct(
        private ?CategoryService $categoryService = null
    ) {
        $this->categoryService = $categoryService ?? new CategoryService();
    }

    public function index(Request $request): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->categoryService->list($businessId, $request->query());

        return Response::json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? [],
        ], $result['status']);
    }

    public function show(Request $request, string $id): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->categoryService->get((int) $id, $businessId);

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if (isset($result['data'])) {
            $payload['data'] = $result['data'];
        }

        return Response::json($payload, $result['status']);
    }

    public function store(Request $request): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->categoryService->create($businessId, $request->body());

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

    public function update(Request $request, string $id): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->categoryService->update((int) $id, $businessId, $request->body());

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

    public function destroy(Request $request, string $id): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->categoryService->delete((int) $id, $businessId);

        return Response::json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['status']);
    }
}
