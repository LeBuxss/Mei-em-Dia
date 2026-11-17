<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;

final class TransactionService
{
    public function __construct(
        private ?Transaction $transactionModel = null,
        private ?Category $categoryModel = null
    ) {
        $this->transactionModel = $transactionModel ?? new Transaction();
        $this->categoryModel = $categoryModel ?? new Category();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{
     *         transactions: list<array<string, mixed>>,
     *         pagination: array{total: int, page: int, limit: int, total_pages: int}
     *     }
     * }
     */
    public function list(int $businessId, array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 10)));

        $transactions = $this->transactionModel->allByBusiness($businessId, $filters, $page, $limit);
        $total = $this->transactionModel->countByBusiness($businessId, $filters);
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Movimentações listadas com sucesso.',
            'data' => [
                'transactions' => $transactions,
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
     *     data?: array<string, mixed>
     * }
     */
    public function get(int $id, int $businessId): array
    {
        $transaction = $this->transactionModel->findByIdAndBusiness($id, $businessId);

        if ($transaction === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Movimentação não encontrada.',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Movimentação recuperada com sucesso.',
            'data' => $transaction,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array<string, mixed>,
     *     errors?: array<string, list<string>>
     * }
     */
    public function create(int $businessId, array $data): array
    {
        $validation = $this->validateInput($businessId, $data);
        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'status' => $validation['status'],
                'message' => $validation['message'],
                'errors' => $validation['errors'],
            ];
        }

        /** @var array{description: string, amount: float, due_date: string, type: string, category_id: int, status: string, paid_at: ?string} $validated */
        $validated = $validation['validated'];

        $id = $this->transactionModel->create([
            'business_id' => $businessId,
            'category_id' => $validated['category_id'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'status' => $validated['status'],
            'paid_at' => $validated['paid_at'],
        ]);

        $created = $this->transactionModel->findByIdAndBusiness($id, $businessId);

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Movimentação cadastrada com sucesso.',
            'data' => $created ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array<string, mixed>,
     *     errors?: array<string, list<string>>
     * }
     */
    public function update(int $id, int $businessId, array $data): array
    {
        $current = $this->transactionModel->findByIdAndBusiness($id, $businessId);
        if ($current === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Movimentação não encontrada.',
            ];
        }

        // Verificação de concorrência otimista (version)
        $providedVersion = isset($data['version']) ? (int) $data['version'] : null;
        if ($providedVersion === null || $providedVersion <= 0) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'O campo version é obrigatório para controle de concorrência.',
                'errors' => [
                    'version' => ['Informe o número da versão atual do registro.'],
                ],
            ];
        }

        if ($current['version'] !== $providedVersion) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Conflito de concorrência. O registro foi alterado por outro processo. Recarregue os dados e tente novamente.',
            ];
        }

        $validation = $this->validateInput($businessId, $data, $current);
        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'status' => $validation['status'],
                'message' => $validation['message'],
                'errors' => $validation['errors'],
            ];
        }

        /** @var array{description: string, amount: float, due_date: string, type: string, category_id: int, status: string, paid_at: ?string} $validated */
        $validated = $validation['validated'];

        $updated = $this->transactionModel->updateWithVersion(
            $id,
            $businessId,
            $providedVersion,
            $validated
        );

        if (!$updated) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Conflito de concorrência detectado durante a atualização.',
            ];
        }

        $fresh = $this->transactionModel->findByIdAndBusiness($id, $businessId);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Movimentação atualizada com sucesso.',
            'data' => $fresh ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array<string, mixed>,
     *     errors?: array<string, list<string>>
     * }
     */
    public function updateStatus(int $id, int $businessId, array $data): array
    {
        $current = $this->transactionModel->findByIdAndBusiness($id, $businessId);
        if ($current === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Movimentação não encontrada.',
            ];
        }

        $status = (string) ($data['status'] ?? '');
        if (!in_array($status, ['pending', 'paid', 'cancelled'], true)) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Status inválido.',
                'errors' => [
                    'status' => ["O status deve ser 'pending', 'paid' ou 'cancelled'."],
                ],
            ];
        }

        $providedVersion = isset($data['version']) ? (int) $data['version'] : (int) $current['version'];
        if ($current['version'] !== $providedVersion) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Conflito de concorrência. O registro foi alterado por outro processo.',
            ];
        }

        $paidAt = null;
        if ($status === 'paid') {
            $paidAt = !empty($data['paid_at']) ? (string) $data['paid_at'] : date('Y-m-d H:i:s');
        }

        $updated = $this->transactionModel->updateStatusWithVersion(
            $id,
            $businessId,
            $providedVersion,
            $status,
            $paidAt
        );

        if (!$updated) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Conflito de concorrência ao atualizar status.',
            ];
        }

        $fresh = $this->transactionModel->findByIdAndBusiness($id, $businessId);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Status da movimentação atualizado com sucesso.',
            'data' => $fresh ?? [],
        ];
    }

    /**
     * @return array{success: bool, status: int, message: string}
     */
    public function delete(int $id, int $businessId): array
    {
        $current = $this->transactionModel->findByIdAndBusiness($id, $businessId);
        if ($current === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Movimentação não encontrada.',
            ];
        }

        $this->transactionModel->delete($id, $businessId);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Movimentação excluída com sucesso.',
        ];
    }

    /**
     * Exporta as movimentações em formato CSV formatado para padrão brasileiro.
     *
     * @param array<string, mixed> $filters
     */
    public function exportCsv(int $businessId, array $filters = []): string
    {
        // Busca até 1000 registros para exportação
        $transactions = $this->transactionModel->allByBusiness($businessId, $filters, 1, 1000);

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return '';
        }

        // BOM para compatibilidade com Microsoft Excel em UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        // Cabeçalho CSV
        fputcsv($output, [
            'ID',
            'Vencimento',
            'Descrição',
            'Categoria',
            'Tipo',
            'Valor (R$)',
            'Status',
            'Data de Pagamento',
        ], ';');

        foreach ($transactions as $tx) {
            $tipo = $tx['type'] === 'income' ? 'Receita' : 'Despesa';
            $statusMap = [
                'paid' => 'Pago',
                'pending' => 'Pendente',
                'cancelled' => 'Cancelado',
            ];
            $status = $statusMap[$tx['status']] ?? $tx['status'];

            fputcsv($output, [
                $tx['id'],
                $tx['due_date'],
                $tx['description'],
                $tx['category_name'],
                $tipo,
                number_format((float) $tx['amount'], 2, ',', '.'),
                $status,
                $tx['paid_at'] ?? '-',
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv !== false ? $csv : '';
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $current
     * @return array{
     *     errors: array<string, list<string>>,
     *     status: int,
     *     message: string,
     *     validated: array{
     *         description: string,
     *         amount: float,
     *         due_date: string,
     *         type: string,
     *         category_id: int,
     *         status: string,
     *         paid_at: ?string
     *     }
     * }
     */
    private function validateInput(int $businessId, array $data, ?array $current = null): array
    {
        $errors = [];
        $status = 422;
        $message = 'Dados inválidos.';

        $description = trim((string) ($data['description'] ?? ($current['description'] ?? '')));
        if ($description === '') {
            $errors['description'][] = 'A descrição da movimentação é obrigatória.';
        } elseif (mb_strlen($description) > 180) {
            $errors['description'][] = 'A descrição deve ter no máximo 180 caracteres.';
        }

        $rawAmount = $data['amount'] ?? ($current['amount'] ?? null);
        $amount = 0.0;
        if ($rawAmount === null || !is_numeric($rawAmount)) {
            $errors['amount'][] = 'O valor da movimentação é obrigatório e deve ser numérico.';
        } else {
            $amount = (float) $rawAmount;
            if ($amount <= 0) {
                $errors['amount'][] = 'O valor deve ser maior que zero.';
            }
        }

        $dueDate = (string) ($data['due_date'] ?? ($current['due_date'] ?? ''));
        if ($dueDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors['due_date'][] = 'A data de vencimento é obrigatória no formato AAAA-MM-DD.';
        }

        $type = (string) ($data['type'] ?? ($current['type'] ?? ''));
        if (!in_array($type, ['income', 'expense'], true)) {
            $errors['type'][] = "O tipo da movimentação deve ser 'income' ou 'expense'.";
        }

        $txStatus = (string) ($data['status'] ?? ($current['status'] ?? 'pending'));
        if (!in_array($txStatus, ['pending', 'paid', 'cancelled'], true)) {
            $errors['status'][] = "O status deve ser 'pending', 'paid' ou 'cancelled'.";
        }

        $categoryId = (int) ($data['category_id'] ?? ($current['category_id'] ?? 0));
        if ($categoryId <= 0) {
            $errors['category_id'][] = 'A categoria é obrigatória.';
        } else {
            $category = $this->categoryModel->findByIdAndBusiness($categoryId, $businessId);
            if ($category === null) {
                $errors['category_id'][] = 'A categoria selecionada não foi encontrada ou não pertence ao seu negócio.';
            } elseif ($type !== '' && $category['type'] !== $type) {
                $status = 409;
                $message = 'Categoria incompatível com o tipo da movimentação.';
                $errors['category_id'][] = "A categoria selecionada é do tipo '{$category['type']}', mas a movimentação é do tipo '{$type}'.";
            }
        }

        $paidAt = null;
        if ($txStatus === 'paid') {
            $paidAt = !empty($data['paid_at']) ? (string) $data['paid_at'] : date('Y-m-d H:i:s');
        }

        return [
            'errors' => $errors,
            'status' => $status,
            'message' => $message,
            'validated' => [
                'description' => $description,
                'amount' => $amount,
                'due_date' => $dueDate,
                'type' => $type,
                'category_id' => $categoryId,
                'status' => $txStatus,
                'paid_at' => $paidAt,
            ],
        ];
    }
}
