<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Core\Session;
use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        // Garante que o banco está conectado
        Database::getConnection();
        $this->authService = new AuthService(new User(), new Business(), new Category());
        Session::start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Limpa usuários de teste
        $pdo = Database::getConnection();
        $pdo->exec("DELETE FROM users WHERE email IN ('unit_test@email.com', 'novo_usuario@email.com')");
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_register_creates_user_business_and_default_categories(): void
    {
        $result = $this->authService->register([
            'name' => 'Novo Usuário',
            'email' => 'novo_usuario@email.com',
            'password' => 'SenhaForte123',
            'business_name' => 'Oficina do Novo Usuário',
        ]);

        self::assertTrue($result['success']);
        self::assertSame(201, $result['status']);
        self::assertArrayHasKey('data', $result);
        self::assertSame('Novo Usuário', $result['data']['user']['name']);
        self::assertSame('novo_usuario@email.com', $result['data']['user']['email']);
        self::assertSame('Oficina do Novo Usuário', $result['data']['business']['business_name']);

        // Verifica persistência no banco
        $pdo = Database::getConnection();
        $stmtCat = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE business_id = :business_id');
        $stmtCat->execute(['business_id' => $result['data']['business']['id']]);
        self::assertSame(6, (int) $stmtCat->fetchColumn());

        // Verifica que a sessão foi iniciada
        self::assertSame($result['data']['user']['id'], Session::get('user_id'));
    }

    public function test_register_fails_with_validation_errors_for_empty_fields(): void
    {
        $result = $this->authService->register([
            'name' => '',
            'email' => 'invalido',
            'password' => '123',
            'business_name' => '',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertArrayHasKey('errors', $result);
        self::assertArrayHasKey('name', $result['errors']);
        self::assertArrayHasKey('email', $result['errors']);
        self::assertArrayHasKey('password', $result['errors']);
        self::assertArrayHasKey('business_name', $result['errors']);
    }

    public function test_register_fails_when_email_already_exists(): void
    {
        // pedro@email.com foi criado no seed
        $result = $this->authService->register([
            'name' => 'Pedro Clone',
            'email' => 'pedro@email.com',
            'password' => 'SenhaSegura123',
            'business_name' => 'Outra Empresa',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(409, $result['status']);
        self::assertSame('E-mail já cadastrado.', $result['message']);
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $result = $this->authService->login([
            'email' => 'pedro@email.com',
            'password' => 'SenhaSegura123',
        ]);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        self::assertSame('pedro@email.com', $result['data']['user']['email']);
        self::assertSame(1, Session::get('user_id'));
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $result = $this->authService->login([
            'email' => 'pedro@email.com',
            'password' => 'SenhaErrada123',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(401, $result['status']);
        self::assertSame('Credenciais inválidas.', $result['message']);
        self::assertNull(Session::get('user_id'));
    }

    public function test_login_fails_when_user_does_not_exist(): void
    {
        $result = $this->authService->login([
            'email' => 'naoexiste@email.com',
            'password' => 'SenhaQualquer123',
        ]);

        self::assertFalse($result['success']);
        self::assertSame(401, $result['status']);
    }

    public function test_logout_clears_session(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $this->authService->logout();

        self::assertNull(Session::get('user_id'));
    }

    public function test_me_returns_authenticated_user_and_business(): void
    {
        Session::set('user_id', 1);
        Session::set('business_id', 1);

        $me = $this->authService->me();

        self::assertNotNull($me);
        self::assertSame(1, $me['user']['id']);
        self::assertSame('pedro@email.com', $me['user']['email']);
        self::assertSame('Pedro Tecnologia MEI', $me['business']['business_name']);
    }

    public function test_me_returns_null_when_unauthenticated(): void
    {
        self::assertNull($this->authService->me());
    }
}
