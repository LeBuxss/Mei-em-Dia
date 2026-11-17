<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    /**
     * @return array{id: int, name: string, email: string, password_hash: string, created_at: string}|null
     */
    public function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();

        /** @var array{id: int, name: string, email: string, password_hash: string, created_at: string}|null */
        return $user ?: null;
    }

    /**
     * @return array{id: int, name: string, email: string, password_hash: string, created_at: string}|null
     */
    public function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, created_at FROM users WHERE email = :email');
        $stmt->execute(['email' => strtolower(trim($email))]);

        $user = $stmt->fetch();

        /** @var array{id: int, name: string, email: string, password_hash: string, created_at: string}|null */
        return $user ?: null;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, created_at) VALUES (:name, :email, :password_hash, NOW())'
        );
        $stmt->execute([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
        ]);

        return (int) $pdo->lastInsertId();
    }
}
