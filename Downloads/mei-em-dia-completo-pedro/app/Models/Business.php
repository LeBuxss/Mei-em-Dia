<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Business
{
    /**
     * @return array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string}|null
     */
    public function findByUserId(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, user_id, business_name, cnpj, created_at FROM businesses WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $business = $stmt->fetch();

        /** @var array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string}|null */
        return $business ?: null;
    }

    /**
     * @return array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string}|null
     */
    public function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, user_id, business_name, cnpj, created_at FROM businesses WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $business = $stmt->fetch();

        /** @var array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string}|null */
        return $business ?: null;
    }

    /**
     * @return array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string}|null
     */
    public function findByCnpj(string $cnpj): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, user_id, business_name, cnpj, created_at FROM businesses WHERE cnpj = :cnpj');
        $stmt->execute(['cnpj' => $cnpj]);

        $business = $stmt->fetch();

        /** @var array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string}|null */
        return $business ?: null;
    }

    public function create(int $userId, string $businessName, ?string $cnpj = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO businesses (user_id, business_name, cnpj, created_at) VALUES (:user_id, :business_name, :cnpj, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'business_name' => trim($businessName),
            'cnpj' => $cnpj !== null && trim($cnpj) !== '' ? trim($cnpj) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, string $businessName, ?string $cnpj = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE businesses SET business_name = :business_name, cnpj = :cnpj WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'business_name' => trim($businessName),
            'cnpj' => $cnpj !== null && trim($cnpj) !== '' ? trim($cnpj) : null,
        ]);
    }
}
