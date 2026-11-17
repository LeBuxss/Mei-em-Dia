<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionService;
use PHPUnit\Framework\TestCase;

final class TransactionServiceTest extends TestCase
{
    private TransactionService $transactionService;
    private int $businessId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        Database::getConnection();
        $this->transactionService = new TransactionService(new Transaction(), new Category());
    }

    protected function tearDown(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec("DELETE FROM transactions WHERE description LIKE 'Teste Unit Tx%'");
        parent::tearDown();
    }

    public function test_create_income_transaction_success(): void
    {
        // Categoria 2 = Prestação de Serviços (income)
        $result = $this->transactionService->create($this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Receita',
            'amount' => 1250.50,
            'due_date' => '2026-11-20',
            'status' => 'pending',
        ]);

        self::assertTrue($result['success']);
        self::assertSame(201, $result['status']);
        self::assertSame('Teste Unit Tx Receita', $result['data']['description']);
        self::assertSame('1250.50', $result['data']['amount']);
        self::assertSame('pending', $result['data']['status']);
        self::assertSame(1, (int) $result['data']['version']);
        self::assertNull($result['data']['paid_at']);
    }

    public function test_create_expense_transaction_success(): void
    {
        // Categoria 3 = Alimentação (expense)
        $result = $this->transactionService->create($this->businessId, [
            'category_id' => 3,
            'type' => 'expense',
            'description' => 'Teste Unit Tx Despesa',
            'amount' => 85.00,
            'due_date' => '2026-11-15',
            'status' => 'paid',
        ]);

        self::assertTrue($result['success']);
        self::assertSame(201, $result['status']);
        self::assertSame('paid', $result['data']['status']);
        self::assertNotNull($result['data']['paid_at']);
    }

    public function test_create_fails_with_non_positive_amount(): void
    {
        $result = $this->transactionService->create($this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Valor Invalido',
            'amount' => -10.00,
            'due_date' => '2026-11-20',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertArrayHasKey('errors', $result);
        self::assertArrayHasKey('amount', $result['errors']);
    }

    public function test_create_fails_with_invalid_date(): void
    {
        $result = $this->transactionService->create($this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Data Invalida',
            'amount' => 100.00,
            'due_date' => 'data-errada',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertArrayHasKey('due_date', $result['errors']);
    }

    public function test_create_fails_when_category_type_is_incompatible(): void
    {
        // Categoria 3 é 'expense', mas a movimentação declarada é 'income'
        $result = $this->transactionService->create($this->businessId, [
            'category_id' => 3,
            'type' => 'income',
            'description' => 'Teste Unit Tx Incompativel',
            'amount' => 100.00,
            'due_date' => '2026-11-20',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(409, $result['status']);
        self::assertSame('Categoria incompatível com o tipo da movimentação.', $result['message']);
    }

    public function test_optimistic_concurrency_update_detects_version_conflict(): void
    {
        $created = $this->transactionService->create($this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Concorrencia',
            'amount' => 500.00,
            'due_date' => '2026-11-20',
            'status' => 'pending',
        ]);

        $id = (int) $created['data']['id'];
        $initialVersion = (int) $created['data']['version']; // 1

        // 1. Primeira atualização com versão correta (1 -> 2)
        $update1 = $this->transactionService->update($id, $this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Atualizado Aba A',
            'amount' => 600.00,
            'due_date' => '2026-11-20',
            'status' => 'pending',
            'version' => $initialVersion,
        ]);

        self::assertTrue($update1['success']);
        self::assertSame(2, (int) $update1['data']['version']);

        // 2. Segunda atualização simulando outra aba tentando atualizar com a versão antiga (1)
        $update2 = $this->transactionService->update($id, $this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Atualizado Aba B Conflito',
            'amount' => 700.00,
            'due_date' => '2026-11-20',
            'status' => 'pending',
            'version' => $initialVersion, // Versão 1 desatualizada!
        ]);

        self::assertFalse($update2['success']);
        self::assertSame(409, $update2['status']);
        self::assertStringContainsString('Conflito de concorrência', $update2['message']);
    }

    public function test_update_status_paid_sets_paid_at_and_increments_version(): void
    {
        $created = $this->transactionService->create($this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Status',
            'amount' => 300.00,
            'due_date' => '2026-11-20',
            'status' => 'pending',
        ]);

        $id = (int) $created['data']['id'];
        $v1 = (int) $created['data']['version'];

        $result = $this->transactionService->updateStatus($id, $this->businessId, [
            'status' => 'paid',
            'version' => $v1,
        ]);

        self::assertTrue($result['success']);
        self::assertSame('paid', $result['data']['status']);
        self::assertNotNull($result['data']['paid_at']);
        self::assertSame($v1 + 1, (int) $result['data']['version']);

        // Agora altera de volta para pending -> paid_at deve ser anulado
        $v2 = (int) $result['data']['version'];
        $resultPending = $this->transactionService->updateStatus($id, $this->businessId, [
            'status' => 'pending',
            'version' => $v2,
        ]);

        self::assertTrue($resultPending['success']);
        self::assertSame('pending', $resultPending['data']['status']);
        self::assertNull($resultPending['data']['paid_at']);
    }

    public function test_delete_transaction_success(): void
    {
        $created = $this->transactionService->create($this->businessId, [
            'category_id' => 2,
            'type' => 'income',
            'description' => 'Teste Unit Tx Delete',
            'amount' => 150.00,
            'due_date' => '2026-11-20',
        ]);

        $id = (int) $created['data']['id'];

        $result = $this->transactionService->delete($id, $this->businessId);
        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);

        // Tentar buscar novamente deve retornar 404
        $check = $this->transactionService->get($id, $this->businessId);
        self::assertFalse($check['success']);
        self::assertSame(404, $check['status']);
    }

    public function test_isolation_cannot_access_transaction_from_another_business(): void
    {
        // Transação 1 pertence ao business 1 (seed).
        // Acesso com business 999 deve retornar 404
        $result = $this->transactionService->get(1, 999);

        self::assertFalse($result['success']);
        self::assertSame(404, $result['status']);
    }
}
