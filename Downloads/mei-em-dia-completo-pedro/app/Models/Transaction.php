<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Transaction
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array{
     *     id: int,
     *     business_id: int,
     *     category_id: int,
     *     category_name: string,
     *     type: string,
     *     description: string,
     *     amount: string,
     *     due_date: string,
     *     status: string,
     *     paid_at: ?string,
     *     version: int,
     *     created_at: string,
     *     updated_at: string
     * }>
     */
    public function allByBusiness(int $businessId, array $filters = [], int $page = 1, int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $offset = max(0, ($page - 1) * $limit);

        $conditions = ['t.business_id = :business_id'];
        $params = ['business_id' => $businessId];

        if (!empty($filters['type']) && in_array($filters['type'], ['income', 'expense'], true)) {
            $conditions[] = 't.type = :type';
            $params['type'] = (string) $filters['type'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'paid', 'cancelled'], true)) {
            $conditions[] = 't.status = :status';
            $params['status'] = (string) $filters['status'];
        }

        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $conditions[] = 't.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['start_date'])) {
            $conditions[] = 't.due_date >= :start_date';
            $params['start_date'] = (string) $filters['start_date'];
        }

        if (!empty($filters['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['end_date'])) {
            $conditions[] = 't.due_date <= :end_date';
            $params['end_date'] = (string) $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = 't.description LIKE :search';
            $params['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT 
                    t.id, t.business_id, t.category_id, c.name AS category_name,
                    t.type, t.description, t.amount, t.due_date, t.status,
                    t.paid_at, t.version, t.created_at, t.updated_at
                FROM transactions t
                JOIN categories c ON c.id = t.category_id AND c.business_id = t.business_id
                WHERE {$where}
                ORDER BY t.due_date DESC, t.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        /** @var list<array{id: int, business_id: int, category_id: int, category_name: string, type: string, description: string, amount: string, due_date: string, status: string, paid_at: ?string, version: int, created_at: string, updated_at: string}> */
        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countByBusiness(int $businessId, array $filters = []): int
    {
        $pdo = Database::getConnection();

        $conditions = ['t.business_id = :business_id'];
        $params = ['business_id' => $businessId];

        if (!empty($filters['type']) && in_array($filters['type'], ['income', 'expense'], true)) {
            $conditions[] = 't.type = :type';
            $params['type'] = (string) $filters['type'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'paid', 'cancelled'], true)) {
            $conditions[] = 't.status = :status';
            $params['status'] = (string) $filters['status'];
        }

        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $conditions[] = 't.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['start_date'])) {
            $conditions[] = 't.due_date >= :start_date';
            $params['start_date'] = (string) $filters['start_date'];
        }

        if (!empty($filters['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['end_date'])) {
            $conditions[] = 't.due_date <= :end_date';
            $params['end_date'] = (string) $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = 't.description LIKE :search';
            $params['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT COUNT(*) FROM transactions t WHERE {$where}";
        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{
     *     id: int,
     *     business_id: int,
     *     category_id: int,
     *     category_name: string,
     *     type: string,
     *     description: string,
     *     amount: string,
     *     due_date: string,
     *     status: string,
     *     paid_at: ?string,
     *     version: int,
     *     created_at: string,
     *     updated_at: string
     * }|null
     */
    public function findByIdAndBusiness(int $id, int $businessId): ?array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT 
                    t.id, t.business_id, t.category_id, c.name AS category_name,
                    t.type, t.description, t.amount, t.due_date, t.status,
                    t.paid_at, t.version, t.created_at, t.updated_at
                FROM transactions t
                JOIN categories c ON c.id = t.category_id AND c.business_id = t.business_id
                WHERE t.id = :id AND t.business_id = :business_id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'business_id' => $businessId]);

        $tx = $stmt->fetch();

        /** @var array{id: int, business_id: int, category_id: int, category_name: string, type: string, description: string, amount: string, due_date: string, status: string, paid_at: ?string, version: int, created_at: string, updated_at: string}|null */
        return $tx ?: null;
    }

    /**
     * @param array{
     *     business_id: int,
     *     category_id: int,
     *     type: string,
     *     description: string,
     *     amount: float,
     *     due_date: string,
     *     status: string,
     *     paid_at: ?string
     * } $data
     */
    public function create(array $data): int
    {
        $pdo = Database::getConnection();
        $sql = 'INSERT INTO transactions (
                    business_id, category_id, type, description, amount, due_date, status, paid_at, version, created_at
                ) VALUES (
                    :business_id, :category_id, :type, :description, :amount, :due_date, :status, :paid_at, 1, NOW()
                )';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'business_id' => $data['business_id'],
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'description' => trim($data['description']),
            'amount' => $data['amount'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
            'paid_at' => $data['paid_at'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Atualização atômica com controle otimista de concorrência por versão.
     *
     * @param array{
     *     category_id: int,
     *     type: string,
     *     description: string,
     *     amount: float,
     *     due_date: string,
     *     status: string,
     *     paid_at: ?string
     * } $data
     */
    public function updateWithVersion(int $id, int $businessId, int $version, array $data): bool
    {
        $pdo = Database::getConnection();
        $sql = 'UPDATE transactions
                SET category_id = :category_id,
                    type = :type,
                    description = :description,
                    amount = :amount,
                    due_date = :due_date,
                    status = :status,
                    paid_at = :paid_at,
                    version = version + 1
                WHERE id = :id AND business_id = :business_id AND version = :version';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'business_id' => $businessId,
            'version' => $version,
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'description' => trim($data['description']),
            'amount' => $data['amount'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
            'paid_at' => $data['paid_at'],
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Atualização atômica de status com controle de versão.
     */
    public function updateStatusWithVersion(int $id, int $businessId, int $version, string $status, ?string $paidAt): bool
    {
        $pdo = Database::getConnection();
        $sql = 'UPDATE transactions
                SET status = :status,
                    paid_at = :paid_at,
                    version = version + 1
                WHERE id = :id AND business_id = :business_id AND version = :version';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'business_id' => $businessId,
            'version' => $version,
            'status' => $status,
            'paid_at' => $paidAt,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $businessId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id AND business_id = :business_id');
        $stmt->execute(['id' => $id, 'business_id' => $businessId]);

        return $stmt->rowCount() > 0;
    }
}
