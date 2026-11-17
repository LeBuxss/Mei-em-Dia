<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\DashboardService;
use PHPUnit\Framework\TestCase;

final class DashboardServiceTest extends TestCase
{
    private DashboardService $dashboardService;
    private int $businessId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        Database::getConnection();
        $this->dashboardService = new DashboardService();
    }

    protected function tearDown(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec("DELETE FROM transactions WHERE description LIKE 'Teste Unit Dash%'");
        parent::tearDown();
    }

    public function test_dashboard_summary_calculates_correct_totals_for_seed_data(): void
    {
        // No seed de novembro/2026 para business_id = 1:
        // Receita paga: 3500.00
        // Despesas pagas: 120.00 + 150.00 = 270.00
        // Saldo: 3500 - 270 = 3230.00
        // Receita pendente: 1500.00
        // Despesas pendentes: 450.00 + 200.00 = 650.00
        // Atrasadas: 1 (despesa de 200.00 com vencimento em 2026-11-02 < 2026-11-17)
        $result = $this->dashboardService->getSummary($this->businessId, 11, 2026);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        $data = $result['data'];

        self::assertSame(11, $data['month']);
        self::assertSame(2026, $data['year']);
        self::assertSame(3500.00, $data['total_income']);
        self::assertSame(270.00, $data['total_expenses']);
        self::assertSame(3230.00, $data['balance']);
        self::assertSame(1500.00, $data['pending_income']);
        self::assertSame(650.00, $data['pending_expenses']);
        self::assertSame(1, $data['overdue_transactions']);
        self::assertNotEmpty($data['expenses_by_category']);
        self::assertNotEmpty($data['incomes_by_category']);
    }

    public function test_dashboard_ignores_cancelled_transactions(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec(
            "INSERT INTO transactions (business_id, category_id, type, description, amount, due_date, status, paid_at, version, created_at)
             VALUES (1, 2, 'income', 'Teste Unit Dash Cancelada', 9999.00, '2026-11-10', 'cancelled', NULL, 1, NOW())"
        );

        $result = $this->dashboardService->getSummary($this->businessId, 11, 2026);
        $data = $result['data'];

        // A receita cancelada de 9999 não deve alterar o total_income de 3500
        self::assertSame(3500.00, $data['total_income']);
        self::assertSame(3230.00, $data['balance']);
    }

    public function test_dashboard_respects_month_and_year_filters(): void
    {
        // Mês sem movimentações
        $result = $this->dashboardService->getSummary($this->businessId, 1, 2025);
        $data = $result['data'];

        self::assertSame(0.0, $data['total_income']);
        self::assertSame(0.0, $data['total_expenses']);
        self::assertSame(0.0, $data['balance']);
        self::assertSame(0.0, $data['pending_income']);
        self::assertSame(0.0, $data['pending_expenses']);
        self::assertSame(0, $data['overdue_transactions']);
    }

    public function test_dashboard_data_isolation(): void
    {
        // Negócio sem movimentações (ex: 999)
        $result = $this->dashboardService->getSummary(999, 11, 2026);
        $data = $result['data'];

        self::assertSame(0.0, $data['total_income']);
        self::assertSame(0.0, $data['total_expenses']);
        self::assertSame(0.0, $data['balance']);
    }
}
