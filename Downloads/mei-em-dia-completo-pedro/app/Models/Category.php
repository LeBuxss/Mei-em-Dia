<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Category
{
    /**
     * @var list<array{name: string, type: string}>
     */
    public const DEFAULT_CATEGORIES = [
        ['name' => 'Vendas de Produtos', 'type' => 'income'],
        ['name' => 'Prestação de Serviços', 'type' => 'income'],
        ['name' => 'Alimentação', 'type' => 'expense'],
        ['name' => 'Transporte', 'type' => 'expense'],
        ['name' => 'Materiais e Insumos', 'type' => 'expense'],
        ['name' => 'Internet e Telefonia', 'type' => 'expense'],
    ];

    public function createDefaultsForBusiness(int $businessId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO categories (business_id, name, type, created_at) VALUES (:business_id, :name, :type, NOW())'
        );

        foreach (self::DEFAULT_CATEGORIES as $cat) {
            $stmt->execute([
                'business_id' => $businessId,
                'name' => $cat['name'],
                'type' => $cat['type'],
            ]);
        }
    }

    /**
     * @return list<array{id: int, business_id: int, name: string, type: string, created_at: string}>
     */
    public function allByBusiness(int $businessId, ?string $type = null, int $page = 1, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $offset = max(0, ($page - 1) * $limit);

        if ($type !== null && in_array($type, ['income', 'expense'], true)) {
            $sql = 'SELECT id, business_id, name, type, created_at FROM categories 
                    WHERE business_id = :business_id AND type = :type 
                    ORDER BY name ASC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':business_id', $businessId, PDO::PARAM_INT);
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } else {
            $sql = 'SELECT id, business_id, name, type, created_at FROM categories 
                    WHERE business_id = :business_id 
                    ORDER BY name ASC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':business_id', $businessId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        /** @var list<array{id: int, business_id: int, name: string, type: string, created_at: string}> */
        return $stmt->fetchAll();
    }

    public function countByBusiness(int $businessId, ?string $type = null): int
    {
        $pdo = Database::getConnection();

        if ($type !== null && in_array($type, ['income', 'expense'], true)) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE business_id = :business_id AND type = :type');
            $stmt->execute(['business_id' => $businessId, 'type' => $type]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE business_id = :business_id');
            $stmt->execute(['business_id' => $businessId]);
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{id: int, business_id: int, name: string, type: string, created_at: string}|null
     */
    public function findByIdAndBusiness(int $id, int $businessId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, business_id, name, type, created_at FROM categories WHERE id = :id AND business_id = :business_id'
        );
        $stmt->execute(['id' => $id, 'business_id' => $businessId]);

        $category = $stmt->fetch();

        /** @var array{id: int, business_id: int, name: string, type: string, created_at: string}|null */
        return $category ?: null;
    }

    /**
     * @return array{id: int, business_id: int, name: string, type: string, created_at: string}|null
     */
    public function findByNameTypeAndBusiness(string $name, string $type, int $businessId, ?int $excludeId = null): ?array
    {
        $pdo = Database::getConnection();

        if ($excludeId !== null) {
            $sql = 'SELECT id, business_id, name, type, created_at FROM categories 
                    WHERE business_id = :business_id AND name = :name AND type = :type AND id != :exclude_id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'business_id' => $businessId,
                'name' => trim($name),
                'type' => $type,
                'exclude_id' => $excludeId,
            ]);
        } else {
            $sql = 'SELECT id, business_id, name, type, created_at FROM categories 
                    WHERE business_id = :business_id AND name = :name AND type = :type';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'business_id' => $businessId,
                'name' => trim($name),
                'type' => $type,
            ]);
        }

        $category = $stmt->fetch();

        /** @var array{id: int, business_id: int, name: string, type: string, created_at: string}|null */
        return $category ?: null;
    }

    public function create(int $businessId, string $name, string $type): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO categories (business_id, name, type, created_at) VALUES (:business_id, :name, :type, NOW())'
        );
        $stmt->execute([
            'business_id' => $businessId,
            'name' => trim($name),
            'type' => $type,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, int $businessId, string $name, string $type): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE categories SET name = :name, type = :type WHERE id = :id AND business_id = :business_id'
        );

        return $stmt->execute([
            'id' => $id,
            'business_id' => $businessId,
            'name' => trim($name),
            'type' => $type,
        ]);
    }

    public function delete(int $id, int $businessId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id AND business_id = :business_id');

        return $stmt->execute([
            'id' => $id,
            'business_id' => $businessId,
        ]);
    }

    public function hasTransactions(int $id, int $businessId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE category_id = :id AND business_id = :business_id');
        $stmt->execute([
            'id' => $id,
            'business_id' => $businessId,
        ]);

        return ((int) $stmt->fetchColumn()) > 0;
    }
}
