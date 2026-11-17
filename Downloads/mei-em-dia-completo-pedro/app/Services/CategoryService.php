<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;

final class CategoryService
{
    public function __construct(
        private ?Category $categoryModel = null
    ) {
        $this->categoryModel = $categoryModel ?? new Category();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{
     *         categories: list<array{id: int, business_id: int, name: string, type: string, created_at: string}>,
     *         pagination: array{total: int, page: int, limit: int, total_pages: int}
     *     }
     * }
     */
    public function list(int $businessId, array $filters = []): array
    {
        $type = isset($filters['type']) && in_array($filters['type'], ['income', 'expense'], true)
            ? (string) $filters['type']
            : null;

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 10)));

        $categories = $this->categoryModel->allByBusiness($businessId, $type, $page, $limit);
        $total = $this->categoryModel->countByBusiness($businessId, $type);
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Categorias listadas com sucesso.',
            'data' => [
                'categories' => $categories,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => $totalPages,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{id: int, business_id: int, name: string, type: string, created_at: string}
     * }
     */
    public function get(int $id, int $businessId): array
    {
        $category = $this->categoryModel->findByIdAndBusiness($id, $businessId);

        if ($category === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Categoria não encontrada.',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Categoria recuperada com sucesso.',
            'data' => $category,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{id: int, business_id: int, name: string, type: string, created_at: string},
     *     errors?: array<string, list<string>>
     * }
     */
    public function create(int $businessId, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = (string) ($data['type'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'][] = 'O nome da categoria é obrigatório.';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            $errors['name'][] = 'O nome da categoria deve ter entre 2 e 80 caracteres.';
        }

        if ($type === '') {
            $errors['type'][] = 'O tipo da categoria é obrigatório.';
        } elseif (!in_array($type, ['income', 'expense'], true)) {
            $errors['type'][] = "O tipo da categoria deve ser 'income' ou 'expense'.";
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Dados inválidos.',
                'errors' => $errors,
            ];
        }

        // Verifica duplicidade dentro do mesmo negócio
        $existing = $this->categoryModel->findByNameTypeAndBusiness($name, $type, $businessId);
        if ($existing !== null) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Categoria duplicada.',
                'errors' => [
                    'name' => ['Já existe uma categoria com este nome e tipo no seu negócio.'],
                ],
            ];
        }

        $id = $this->categoryModel->create($businessId, $name, $type);
        $category = $this->categoryModel->findByIdAndBusiness($id, $businessId);

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Categoria cadastrada com sucesso.',
            'data' => $category ?? [
                'id' => $id,
                'business_id' => $businessId,
                'name' => $name,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{id: int, business_id: int, name: string, type: string, created_at: string},
     *     errors?: array<string, list<string>>
     * }
     */
    public function update(int $id, int $businessId, array $data): array
    {
        $category = $this->categoryModel->findByIdAndBusiness($id, $businessId);

        if ($category === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Categoria não encontrada.',
            ];
        }

        $name = trim((string) ($data['name'] ?? $category['name']));
        $type = (string) ($data['type'] ?? $category['type']);

        $errors = [];

        if ($name === '') {
            $errors['name'][] = 'O nome da categoria é obrigatório.';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            $errors['name'][] = 'O nome da categoria deve ter entre 2 e 80 caracteres.';
        }

        if (!in_array($type, ['income', 'expense'], true)) {
            $errors['type'][] = "O tipo da categoria deve ser 'income' ou 'expense'.";
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Dados inválidos.',
                'errors' => $errors,
            ];
        }

        // Verifica duplicidade com outro ID
        $existing = $this->categoryModel->findByNameTypeAndBusiness($name, $type, $businessId, $id);
        if ($existing !== null) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Categoria duplicada.',
                'errors' => [
                    'name' => ['Já existe outra categoria com este nome e tipo no seu negócio.'],
                ],
            ];
        }

        $this->categoryModel->update($id, $businessId, $name, $type);
        $updated = $this->categoryModel->findByIdAndBusiness($id, $businessId);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Categoria atualizada com sucesso.',
            'data' => $updated ?? $category,
        ];
    }

    /**
     * @return array{success: bool, status: int, message: string}
     */
    public function delete(int $id, int $businessId): array
    {
        $category = $this->categoryModel->findByIdAndBusiness($id, $businessId);

        if ($category === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Categoria não encontrada.',
            ];
        }

        if ($this->categoryModel->hasTransactions($id, $businessId)) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Não é possível excluir uma categoria vinculada a movimentações financeiras.',
            ];
        }

        $this->categoryModel->delete($id, $businessId);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Categoria excluída com sucesso.',
        ];
    }
}
