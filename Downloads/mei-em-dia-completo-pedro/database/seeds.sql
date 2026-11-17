-- MEI em Dia - Dados Iniciais de Demonstração (Seeds)
-- Banco de Dados: mei_em_dia

USE mei_em_dia;

-- 1. Usuário de teste (senha: SenhaSegura123)
INSERT INTO users (id, name, email, password_hash, created_at)
VALUES (
    1,
    'Pedro Silva',
    'pedro@email.com',
    '$2y$10$PIO/eBGRhULUxAcy3gLkBe7LZBLwRx4GNhHalQgzCZsp2FS2mHqmy',
    NOW()
)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 2. Empresa MEI do usuário Pedro
INSERT INTO businesses (id, user_id, business_name, cnpj, created_at)
VALUES (
    1,
    1,
    'Pedro Tecnologia MEI',
    '12345678000195',
    NOW()
)
ON DUPLICATE KEY UPDATE business_name = VALUES(business_name);

-- 3. Categorias padrão do negócio
INSERT INTO categories (id, business_id, name, type, created_at)
VALUES
    (1, 1, 'Vendas de Produtos', 'income', NOW()),
    (2, 1, 'Prestação de Serviços', 'income', NOW()),
    (3, 1, 'Alimentação', 'expense', NOW()),
    (4, 1, 'Transporte', 'expense', NOW()),
    (5, 1, 'Materiais e Insumos', 'expense', NOW()),
    (6, 1, 'Internet e Telefonia', 'expense', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 4. Movimentações financeiras de exemplo
INSERT INTO transactions (
    id, business_id, category_id, type, description, amount, due_date, status, paid_at, version, created_at
)
VALUES
    -- Receita paga
    (1, 1, 2, 'income', 'Consultoria em TI', 3500.00, '2026-11-10', 'paid', '2026-11-10 14:30:00', 1, NOW()),
    -- Receita pendente
    (2, 1, 1, 'income', 'Venda de periféricos', 1500.00, '2026-11-25', 'pending', NULL, 1, NOW()),
    -- Despesa paga
    (3, 1, 3, 'expense', 'Almoço com cliente', 120.00, '2026-11-05', 'paid', '2026-11-05 13:00:00', 1, NOW()),
    -- Despesa paga
    (4, 1, 6, 'expense', 'Fatura de Internet Fibra', 150.00, '2026-11-08', 'paid', '2026-11-08 09:15:00', 1, NOW()),
    -- Despesa pendente futura
    (5, 1, 5, 'expense', 'Compra de componentes', 450.00, '2026-11-28', 'pending', NULL, 1, NOW()),
    -- Despesa pendente em atraso (vencimento anterior)
    (6, 1, 4, 'expense', 'Recarga de combustível', 200.00, '2026-11-02', 'pending', NULL, 1, NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);
