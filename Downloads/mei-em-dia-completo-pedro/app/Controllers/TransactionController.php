<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\TransactionService;

final class TransactionController
{
    public function __construct(
        private ?TransactionService $transactionService = null
    ) {
        $this->transactionService = $transactionService ?? new TransactionService();
    }

    public function index(Request $request): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->transactionService->list($businessId, $request->query());

        return Response::json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? [],
        ], $result['status']);
    }

    public function show(Request $request, string $id): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->transactionService->get((int) $id, $businessId);

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

        $result = $this->transactionService->create($businessId, $request->body());

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

        $result = $this->transactionService->update((int) $id, $businessId, $request->body());

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

    public function updateStatus(Request $request, string $id): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $result = $this->transactionService->updateStatus((int) $id, $businessId, $request->body());

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

        $result = $this->transactionService->delete((int) $id, $businessId);

        return Response::json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['status']);
    }

    public function export(Request $request): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $csv = $this->transactionService->exportCsv($businessId, $request->query());

        return new Response(
            $csv,
            200,
            [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="movimentacoes_mei_em_dia.csv"',
            ]
        );
    }
}
