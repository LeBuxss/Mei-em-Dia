<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Models\Category;
use App\Services\CategoryService;
use PHPUnit\Framework\TestCase;

final class CategoryServiceTest extends TestCase
{
    private CategoryService $categoryService;
    private int $businessId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        Database::getConnection();
        $this->categoryService = new CategoryService(new Category());
    }

    protected function tearDown(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec("DELETE FROM categories WHERE name LIKE 'Teste Categoria%'");
        parent::tearDown();
    }

    public function test_list_categories_by_business_with_filters(): void
    {
        $result = $this->categoryService->list($this->businessId, ['type' => 'income']);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('categories', $result['data']);
        self::assertArrayHasKey('pagination', $result['data']);

        foreach ($result['data']['categories'] as $cat) {
            self::assertSame('income', $cat['type']);
        }
    }

    public function test_create_category_success(): void
    {
        $result = $this->categoryService->create($this->businessId, [
            'name' => 'Teste Categoria Nova',
            'type' => 'expense',
        ]);

        self::assertTrue($result['success']);
        self::assertSame(201, $result['status']);
        self::assertSame('Teste Categoria Nova', $result['data']['name']);
        self::assertSame('expense', $result['data']['type']);
    }

    public function test_create_category_fails_with_invalid_type(): void
    {
        $result = $this->categoryService->create($this->businessId, [
            'name' => 'Teste Categoria Invalida',
            'type' => 'invalid_type',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertArrayHasKey('errors', $result);
        self::assertArrayHasKey('type', $result['errors']);
    }

    public function test_create_category_fails_when_duplicate_in_same_business(): void
    {
        // 'Alimentação' (expense) já existe no seed para business_id = 1
        $result = $this->categoryService->create($this->businessId, [
            'name' => 'Alimentação',
            'type' => 'expense',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(409, $result['status']);
        self::assertSame('Categoria duplicada.', $result['message']);
    }

    public function test_update_category_success(): void
    {
        $created = $this->categoryService->create($this->businessId, [
            'name' => 'Teste Categoria Update',
            'type' => 'income',
        ]);

        $id = $created['data']['id'];

        $result = $this->categoryService->update($id, $this->businessId, [
            'name' => 'Teste Categoria Updated',
            'type' => 'income',
        ]);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        self::assertSame('Teste Categoria Updated', $result['data']['name']);
    }

    public function test_update_category_fails_when_duplicate(): void
    {
        $created = $this->categoryService->create($this->businessId, [
            'name' => 'Teste Categoria Dup',
            'type' => 'expense',
        ]);

        $id = $created['data']['id'];

        // Tenta renomear para 'Alimentação' que já existe
        $result = $this->categoryService->update($id, $this->businessId, [
            'name' => 'Alimentação',
            'type' => 'expense',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(409, $result['status']);
        self::assertSame('Categoria duplicada.', $result['message']);
    }

    public function test_delete_category_fails_when_has_transactions(): void
    {
        // Categoria 1 (Vendas de Produtos) possui transações associadas no seed
        $result = $this->categoryService->delete(1, $this->businessId);

        self::assertFalse($result['success']);
        self::assertSame(409, $result['status']);
        self::assertSame('Não é possível excluir uma categoria vinculada a movimentações financeiras.', $result['message']);
    }

    public function test_delete_category_succeeds_when_unused(): void
    {
        $created = $this->categoryService->create($this->businessId, [
            'name' => 'Teste Categoria Exclusao',
            'type' => 'expense',
        ]);

        $id = $created['data']['id'];

        $result = $this->categoryService->delete($id, $this->businessId);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
    }

    public function test_get_category_returns_404_for_other_business(): void
    {
        // Categoria 1 pertence ao business 1. Para outro business (ex: 999), deve retornar 404
        $result = $this->categoryService->get(1, 999);

        self::assertFalse($result['success']);
        self::assertSame(404, $result['status']);
    }
}
