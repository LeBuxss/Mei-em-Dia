<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Throwable;

final class DatabaseTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Database::getConnection();
    }

    public function test_database_connection_can_be_established(): void
    {
        self::assertInstanceOf(PDO::class, $this->pdo);
        self::assertSame('0', (string) $this->pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES));
    }

    public function test_four_core_entities_exist(): void
    {
        $stmt = $this->pdo->query("SHOW TABLES LIKE '%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        self::assertContains('users', $tables);
        self::assertContains('businesses', $tables);
        self::assertContains('categories', $tables);
        self::assertContains('transactions', $tables);
    }

    public function test_sample_seeds_are_present(): void
    {
        // 1. Usuário
        $stmtUser = $this->pdo->query("SELECT * FROM users WHERE email = 'pedro@email.com'");
        $user = $stmtUser->fetch();
        self::assertIsArray($user);
        self::assertSame('Pedro Silva', $user['name']);

        // 2. Negócio
        $stmtBiz = $this->pdo->prepare("SELECT * FROM businesses WHERE user_id = :user_id");
        $stmtBiz->execute(['user_id' => $user['id']]);
        $biz = $stmtBiz->fetch();
        self::assertIsArray($biz);
        self::assertSame('Pedro Tecnologia MEI', $biz['business_name']);

        // 3. Categorias
        $stmtCat = $this->pdo->prepare("SELECT COUNT(*) FROM categories WHERE business_id = :business_id");
        $stmtCat->execute(['business_id' => $biz['id']]);
        self::assertGreaterThanOrEqual(6, (int) $stmtCat->fetchColumn());

        // 4. Movimentações
        $stmtTx = $this->pdo->prepare("SELECT COUNT(*) FROM transactions WHERE business_id = :business_id");
        $stmtTx->execute(['business_id' => $biz['id']]);
        self::assertGreaterThanOrEqual(6, (int) $stmtTx->fetchColumn());
    }

    public function test_unique_email_constraint_on_users(): void
    {
        $this->expectException(PDOException::class);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash) VALUES ('Outro Pedro', 'pedro@email.com', 'hash123')"
        );
        $stmt->execute();
    }

    public function test_one_business_per_user_constraint(): void
    {
        $this->expectException(PDOException::class);

        $stmt = $this->pdo->prepare(
            "INSERT INTO businesses (user_id, business_name, cnpj) VALUES (1, 'Segunda Empresa', '99999999000199')"
        );
        $stmt->execute();
    }

    public function test_positive_amount_check_constraint(): void
    {
        $this->expectException(PDOException::class);

        $stmt = $this->pdo->prepare(
            "INSERT INTO transactions (business_id, category_id, type, description, amount, due_date, status)
             VALUES (1, 1, 'income', 'Valor Invalido', -50.00, '2026-11-20', 'pending')"
        );
        $stmt->execute();
    }

    public function test_paid_status_requires_paid_at_constraint(): void
    {
        $this->expectException(PDOException::class);

        // Status 'paid' sem paid_at viola a constraint chk_paid_at
        $stmt = $this->pdo->prepare(
            "INSERT INTO transactions (business_id, category_id, type, description, amount, due_date, status, paid_at)
             VALUES (1, 1, 'income', 'Pago sem data', 100.00, '2026-11-20', 'paid', NULL)"
        );
        $stmt->execute();
    }

    public function test_foreign_key_category_restrict_delete(): void
    {
        $this->expectException(PDOException::class);

        // A categoria 1 possui transações associadas, logo sua exclusão deve ser rejeitada por RESTRICT
        $this->pdo->exec("DELETE FROM categories WHERE id = 1");
    }

    public function test_database_transaction_commit_and_rollback(): void
    {
        // 1. Teste de rollback quando exceção é lançada
        try {
            Database::transaction(function (PDO $pdo): void {
                $pdo->exec(
                    "INSERT INTO users (name, email, password_hash) VALUES ('Temp', 'temp_rollback@email.com', 'hash')"
                );
                throw new \RuntimeException('Erro simulado para forçar rollback');
            });
        } catch (Throwable) {
            // Esperado
        }

        $stmt = $this->pdo->query("SELECT id FROM users WHERE email = 'temp_rollback@email.com'");
        self::assertFalse($stmt->fetch(), 'O registro temporário não deve existir após rollback.');

        // 2. Teste de commit com sucesso
        $result = Database::transaction(function (PDO $pdo): int {
            $pdo->exec(
                "INSERT INTO users (name, email, password_hash) VALUES ('Temp Commit', 'temp_commit@email.com', 'hash')"
            );
            return (int) $pdo->lastInsertId();
        });

        self::assertGreaterThan(0, $result);

        // Limpeza
        $this->pdo->exec("DELETE FROM users WHERE email = 'temp_commit@email.com'");
    }
}
