<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Business;

final class BusinessService
{
    public function __construct(
        private ?Business $businessModel = null
    ) {
        $this->businessModel = $businessModel ?? new Business();
    }

    /**
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string}
     * }
     */
    public function get(int $userId): array
    {
        $business = $this->businessModel->findByUserId($userId);

        if ($business === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Negócio não encontrado.',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Dados do negócio recuperados com sucesso.',
            'data' => $business,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     success: bool,
     *     status: int,
     *     message: string,
     *     data?: array{id: int, user_id: int, business_name: string, cnpj: ?string, created_at: string},
     *     errors?: array<string, list<string>>
     * }
     */
    public function update(int $userId, array $data): array
    {
        $business = $this->businessModel->findByUserId($userId);

        if ($business === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Negócio não encontrado.',
            ];
        }

        $businessName = trim((string) ($data['business_name'] ?? $business['business_name']));
        $rawCnpj = isset($data['cnpj']) ? (string) $data['cnpj'] : $business['cnpj'];

        $errors = [];

        if ($businessName === '') {
            $errors['business_name'][] = 'O nome do negócio é obrigatório.';
        } elseif (mb_strlen($businessName) < 2 || mb_strlen($businessName) > 150) {
            $errors['business_name'][] = 'O nome do negócio deve ter entre 2 e 150 caracteres.';
        }

        $cleanCnpj = null;
        if ($rawCnpj !== null && trim($rawCnpj) !== '') {
            $cleanCnpj = preg_replace('/\D/', '', $rawCnpj) ?? '';
            if (strlen($cleanCnpj) !== 14) {
                $errors['cnpj'][] = 'O CNPJ deve conter exatamente 14 dígitos numéricos.';
            }
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Dados inválidos.',
                'errors' => $errors,
            ];
        }

        // Se CNPJ foi informado, checa duplicidade com outro negócio
        if ($cleanCnpj !== null) {
            $existing = $this->businessModel->findByCnpj($cleanCnpj);
            if ($existing !== null && $existing['id'] !== $business['id']) {
                return [
                    'success' => false,
                    'status' => 409,
                    'message' => 'CNPJ já utilizado.',
                    'errors' => [
                        'cnpj' => ['Este CNPJ já está cadastrado em outra empresa.'],
                    ],
                ];
            }
        }

        $this->businessModel->update((int) $business['id'], $businessName, $cleanCnpj);

        $updated = $this->businessModel->findById((int) $business['id']);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Perfil do negócio atualizado com sucesso.',
            'data' => $updated ?? $business,
        ];
    }
}
