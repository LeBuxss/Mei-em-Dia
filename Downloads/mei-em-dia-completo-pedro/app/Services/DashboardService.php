<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class DashboardService
{
    /**
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data: array{
     *         month: int,
     *         year: int,
     *         total_income: float,
     *         total_expenses: float,
     *         balance: float,
     *         pending_income: float,
     *         pending_expenses: float,
     *         overdue_transactions: int,
     *         expenses_by_category: list<array{category_id: int, category_name: string, total: float}>,
     *         incomes_by_category: list<array{category_id: int, category_name: string, total: float}>
     *     }
     * }
     */
    public function getSummary(int $businessId, ?int $month = null, ?int $year = null): array
    {
        $targetMonth = ($month !== null && $month >= 1 && $month <= 12) ? $month : (int) date('n');
        $targetYear = ($year !== null && $year >= 2000 && $year <= 2100) ? $year : (int) date('Y');

        $pdo = Database::getConnection();

        // 1. Totais de receitas e despesas pagas no período
        $sqlPaid = 'SELECT 
                        type,
                        COALESCE(SUM(amount), 0) AS total
                    FROM transactions
                    WHERE business_id = :business_id
                      AND status = "paid"
                      AND YEAR(due_date) = :year
                      AND MONTH(due_date) = :month
                    GROUP BY type';

        $stmtPaid = $pdo->prepare($sqlPaid);
        $stmtPaid->execute([
            'business_id' => $businessId,
            'year' => $targetYear,
            'month' => $targetMonth,
        ]);

        $totalIncome = 0.0;
        $totalExpenses = 0.0;

        while ($row = $stmtPaid->fetch()) {
            if ($row['type'] === 'income') {
                $totalIncome = (float) $row['total'];
            } elseif ($row['type'] === 'expense') {
                $totalExpenses = (float) $row['total'];
            }
        }

        $balance = round($totalIncome - $totalExpenses, 2);

        // 2. Totais de pendências (receitas e despesas pendentes no período)
        $sqlPending = 'SELECT 
                           type,
                           COALESCE(SUM(amount), 0) AS total
                       FROM transactions
                       WHERE business_id = :business_id
                         AND status = "pending"
                         AND YEAR(due_date) = :year
                         AND MONTH(due_date) = :month
                       GROUP BY type';

        $stmtPending = $pdo->prepare($sqlPending);
        $stmtPending->execute([
            'business_id' => $businessId,
            'year' => $targetYear,
            'month' => $targetMonth,
        ]);

        $pendingIncome = 0.0;
        $pendingExpenses = 0.0;

        while ($row = $stmtPending->fetch()) {
            if ($row['type'] === 'income') {
                $pendingIncome = (float) $row['total'];
            } elseif ($row['type'] === 'expense') {
                $pendingExpenses = (float) $row['total'];
            }
        }

        // 3. Contas atrasadas (pendentes com vencimento anterior à data de hoje)
        $today = date('Y-m-d');
        $sqlOverdue = 'SELECT COUNT(*) 
                       FROM transactions
                       WHERE business_id = :business_id
                         AND status = "pending"
                         AND due_date < :today
                         AND YEAR(due_date) = :year
                         AND MONTH(due_date) = :month';

        $stmtOverdue = $pdo->prepare($sqlOverdue);
        $stmtOverdue->execute([
            'business_id' => $businessId,
            'today' => $today,
            'year' => $targetYear,
            'month' => $targetMonth,
        ]);

        $overdueCount = (int) $stmtOverdue->fetchColumn();

        // 4. Detalhamento por categoria no mês (para os gráficos)
        $sqlCat = 'SELECT 
                       t.category_id,
                       c.name AS category_name,
                       t.type,
                       COALESCE(SUM(t.amount), 0) AS total
                   FROM transactions t
                   JOIN categories c ON c.id = t.category_id AND c.business_id = t.business_id
                   WHERE t.business_id = :business_id
                     AND t.status = "paid"
                     AND YEAR(t.due_date) = :year
                     AND MONTH(t.due_date) = :month
                   GROUP BY t.category_id, c.name, t.type
                   ORDER BY total DESC';

        $stmtCat = $pdo->prepare($sqlCat);
        $stmtCat->execute([
            'business_id' => $businessId,
            'year' => $targetYear,
            'month' => $targetMonth,
        ]);

        $expensesByCategory = [];
        $incomesByCategory = [];

        while ($row = $stmtCat->fetch()) {
            $catItem = [
                'category_id' => (int) $row['category_id'],
                'category_name' => (string) $row['category_name'],
                'total' => round((float) $row['total'], 2),
            ];

            if ($row['type'] === 'expense') {
                $expensesByCategory[] = $catItem;
            } elseif ($row['type'] === 'income') {
                $incomesByCategory[] = $catItem;
            }
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Indicadores financeiros calculados com sucesso.',
            'data' => [
                'month' => $targetMonth,
                'year' => $targetYear,
                'total_income' => round($totalIncome, 2),
                'total_expenses' => round($totalExpenses, 2),
                'balance' => $balance,
                'pending_income' => round($pendingIncome, 2),
                'pending_expenses' => round($pendingExpenses, 2),
                'overdue_transactions' => $overdueCount,
                'expenses_by_category' => $expensesByCategory,
                'incomes_by_category' => $incomesByCategory,
            ],
        ];
    }

    /**
     * Relatório de faturamento anual para acompanhamento do teto do MEI (DASN-SIMEI).
     *
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data: array{
     *         year: int,
     *         total_annual_revenue: float,
     *         annual_limit: float,
     *         percentage_used: float,
     *         remaining_limit: float,
     *         status: string
     *     }
     * }
     */
    public function getAnnualReport(int $businessId, ?int $year = null): array
    {
        $targetYear = ($year !== null && $year >= 2000 && $year <= 2100) ? $year : (int) date('Y');
        $pdo = Database::getConnection();

        $sql = 'SELECT COALESCE(SUM(amount), 0) AS total
                FROM transactions
                WHERE business_id = :business_id
                  AND type = "income"
                  AND status = "paid"
                  AND YEAR(due_date) = :year';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'business_id' => $businessId,
            'year' => $targetYear,
        ]);

        $totalRevenue = (float) $stmt->fetchColumn();
        $annualLimit = 81000.00;
        $percentage = round(($totalRevenue / $annualLimit) * 100, 2);
        $remaining = max(0.0, round($annualLimit - $totalRevenue, 2));

        $status = 'normal';
        if ($percentage >= 100.0) {
            $status = 'danger';
        } elseif ($percentage >= 80.0) {
            $status = 'warning';
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Relatório de faturamento anual gerado com sucesso.',
            'data' => [
                'year' => $targetYear,
                'total_annual_revenue' => round($totalRevenue, 2),
                'annual_limit' => $annualLimit,
                'percentage_used' => $percentage,
                'remaining_limit' => $remaining,
                'status' => $status,
            ],
        ];
    }
}
