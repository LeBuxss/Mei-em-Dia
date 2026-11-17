<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\DashboardService;

final class DashboardController
{
    public function __construct(
        private ?DashboardService $dashboardService = null
    ) {
        $this->dashboardService = $dashboardService ?? new DashboardService();
    }

    public function index(Request $request): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));

        $month = $request->getQueryParam('month') !== null ? (int) $request->getQueryParam('month') : null;
        $year = $request->getQueryParam('year') !== null ? (int) $request->getQueryParam('year') : null;

        $result = $this->dashboardService->getSummary($businessId, $month, $year);

        return Response::json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status']);
    }

    public function annual(Request $request): Response
    {
        $businessId = (int) ($request->getAttribute('auth_business_id') ?? Session::get('business_id'));
        $year = $request->getQueryParam('year') !== null ? (int) $request->getQueryParam('year') : null;

        $result = $this->dashboardService->getAnnualReport($businessId, $year);

        return Response::json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status']);
    }
}
