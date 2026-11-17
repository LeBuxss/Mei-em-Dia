<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public static function getConnection(?array $config = null): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        if ($config === null) {
            $configPath = dirname(__DIR__, 2) . '/config/database.php';
            /** @var array<string, mixed> $config */
            $config = is_file($configPath) ? require $configPath : [];
        }

        $connection = $config['connection'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $database = $config['database'] ?? 'mei_em_dia';
        $charset = $config['charset'] ?? 'utf8mb4';
        $username = (string) ($config['username'] ?? 'root');
        $password = (string) ($config['password'] ?? '');

        /** @var array<int, mixed> $options */
        $options = $config['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $dsn = "{$connection}:host={$host};port={$port};dbname={$database};charset={$charset}";

        self::$instance = new PDO($dsn, $username, $password, $options);

        return self::$instance;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function resetConnection(): void
    {
        self::$instance = null;
    }

    /**
     * Executa um callback dentro de uma transação com commit e rollback automáticos.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     * @throws Throwable
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getConnection();

        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}
