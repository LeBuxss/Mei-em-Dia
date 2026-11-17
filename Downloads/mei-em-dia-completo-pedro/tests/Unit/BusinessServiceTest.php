<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Models\Business;
use App\Services\BusinessService;
use PHPUnit\Framework\TestCase;

final class BusinessServiceTest extends TestCase
{
    private BusinessService $businessService;
    private int $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        Database::getConnection();
        $this->businessService = new BusinessService(new Business());
    }

    protected function tearDown(): void
    {
        // Restaura valores do seed
        $pdo = Database::getConnection();
        $pdo->exec(
            "UPDATE businesses SET business_name = 'Pedro Tecnologia MEI', cnpj = '12345678000195' WHERE user_id = 1"
        );
        $pdo->exec("DELETE FROM businesses WHERE cnpj = '98765432000188'");
        $pdo->exec("DELETE FROM users WHERE email = 'outro_emp@email.com'");
        parent::tearDown();
    }

    public function test_get_business_success(): void
    {
        $result = $this->businessService->get($this->userId);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        self::assertSame('Pedro Tecnologia MEI', $result['data']['business_name']);
        self::assertSame('12345678000195', $result['data']['cnpj']);
    }

    public function test_get_business_not_found(): void
    {
        $result = $this->businessService->get(9999);

        self::assertFalse($result['success']);
        self::assertSame(404, $result['status']);
    }

    public function test_update_business_success(): void
    {
        $result = $this->businessService->update($this->userId, [
            'business_name' => 'Pedro Soluções MEI',
            'cnpj' => '12.345.678/0001-95',
        ]);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        self::assertSame('Pedro Soluções MEI', $result['data']['business_name']);
        self::assertSame('12345678000195', $result['data']['cnpj']);
    }

    public function test_update_business_fails_with_invalid_cnpj(): void
    {
        $result = $this->businessService->update($this->userId, [
            'cnpj' => '12345', // menos de 14 dígitos
        ]);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertArrayHasKey('errors', $result);
        self::assertArrayHasKey('cnpj', $result['errors']);
    }

    public function test_update_business_fails_when_cnpj_already_used(): void
    {
        // Cria outro usuário e empresa com CNPJ diferente
        $pdo = Database::getConnection();
        $pdo->exec("INSERT INTO users (name, email, password_hash) VALUES ('Outro', 'outro_emp@email.com', 'hash')");
        $otherUserId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO businesses (user_id, business_name, cnpj) VALUES ({$otherUserId}, 'Outra', '98765432000188')");

        // Tenta atualizar a empresa do Pedro com o CNPJ da outra empresa
        $result = $this->businessService->update($this->userId, [
            'cnpj' => '98765432000188',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(409, $result['status']);
        self::assertSame('CNPJ já utilizado.', $result['message']);
    }
}
