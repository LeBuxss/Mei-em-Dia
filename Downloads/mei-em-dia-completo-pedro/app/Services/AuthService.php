<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use PDO;

final class AuthService
{
    public function __construct(
        private ?User $userModel = null,
        private ?Business $businessModel = null,
        private ?Category $categoryModel = null
    ) {
        $this->userModel = $userModel ?? new User();
        $this->businessModel = $businessModel ?? new Business();
        $this->categoryModel = $categoryModel ?? new Category();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{user: array{id: int, name: string, email: string}, business: array{id: int, business_name: string, cnpj: ?string}},
     *     errors?: array<string, list<string>>
     * }
     */
    public function register(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $businessName = trim((string) ($data['business_name'] ?? ''));

        $errors = [];

        if ($name === '') {
            $errors['name'][] = 'O nome é obrigatório.';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'][] = 'O nome deve ter entre 2 e 120 caracteres.';
        }

        if ($email === '') {
            $errors['email'][] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
            $errors['email'][] = 'Informe um e-mail válido com no máximo 180 caracteres.';
        }

        if ($password === '') {
            $errors['password'][] = 'A senha é obrigatória.';
        } elseif (strlen($password) < 8) {
            $errors['password'][] = 'A senha deve conter no mínimo 8 caracteres.';
        }

        if ($businessName === '') {
            $errors['business_name'][] = 'O nome do negócio é obrigatório.';
        } elseif (mb_strlen($businessName) < 2 || mb_strlen($businessName) > 150) {
            $errors['business_name'][] = 'O nome do negócio deve ter entre 2 e 150 caracteres.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Dados inválidos.',
                'errors' => $errors,
            ];
        }

        // Verifica unicidade do e-mail
        if ($this->userModel->findByEmail($email) !== null) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'E-mail já cadastrado.',
                'errors' => [
                    'email' => ['Este e-mail já está sendo utilizado por outra conta.'],
                ],
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        /** @var array{user: array{id: int, name: string, email: string}, business: array{id: int, business_name: string, cnpj: ?string}} $result */
        $result = Database::transaction(function (PDO $pdo) use ($name, $email, $passwordHash, $businessName): array {
            $userId = $this->userModel->create($name, $email, $passwordHash);
            $businessId = $this->businessModel->create($userId, $businessName);
            $this->categoryModel->createDefaultsForBusiness($businessId);

            return [
                'user' => [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                ],
                'business' => [
                    'id' => $businessId,
                    'business_name' => $businessName,
                    'cnpj' => null,
                ],
            ];
        });

        // Inicia a sessão automaticamente após o cadastro
        Session::regenerate();
        Session::set('user_id', $result['user']['id']);
        Session::set('business_id', $result['business']['id']);

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Conta criada com sucesso.',
            'data' => $result,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{user: array{id: int, name: string, email: string}, business: array{id: int, business_name: string, cnpj: ?string}},
     *     errors?: array<string, list<string>>
     * }
     */
    public function login(array $data): array
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $errors = [];
        if ($email === '') {
            $errors['email'][] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Informe um e-mail válido.';
        }

        if ($password === '') {
            $errors['password'][] = 'A senha é obrigatória.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Campos inválidos.',
                'errors' => $errors,
            ];
        }

        $user = $this->userModel->findByEmail($email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Credenciais inválidas.',
            ];
        }

        $business = $this->businessModel->findByUserId((int) $user['id']);
        if ($business === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Negócio não encontrado para este usuário.',
            ];
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('business_id', (int) $business['id']);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Login realizado com sucesso.',
            'data' => [
                'user' => [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                ],
                'business' => [
                    'id' => (int) $business['id'],
                    'business_name' => $business['business_name'],
                    'cnpj' => $business['cnpj'],
                ],
            ],
        ];
    }

    public function logout(): void
    {
        Session::destroy();
    }

    /**
     * @return array{user: array{id: int, name: string, email: string}, business: array{id: int, business_name: string, cnpj: ?string}}|null
     */
    public function me(): ?array
    {
        $userId = Session::get('user_id');
        if ($userId === null) {
            return null;
        }

        $user = $this->userModel->findById((int) $userId);
        if ($user === null) {
            return null;
        }

        $business = $this->businessModel->findByUserId((int) $user['id']);
        if ($business === null) {
            return null;
        }

        return [
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ],
            'business' => [
                'id' => (int) $business['id'],
                'business_name' => $business['business_name'],
                'cnpj' => $business['cnpj'],
            ],
        ];
    }
}
