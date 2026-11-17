# MEI em Dia

Sistema web completo de gestão financeira simplificada para Microempreendedores Individuais (MEIs).

Desenvolvido para o projeto final de **Programação Web II**, seguindo arquitetura Monólito Modular MVC, testes automatizados (PHPUnit), análise estática (PHPStan Nível 5) e boas práticas de segurança (OWASP).

---

## 🚀 Objetivo e Proposta de Valor

Centralizar o controle de receitas, despesas, pagamentos, vencimentos e indicadores financeiros de MEIs e pequenos empreendedores em um único ambiente, eliminando a dependência de anotações dispersas em papel ou planilhas desorganizadas.

---

## 🛠️ Stack Tecnológica

- **Backend:** PHP 8.2+ (MVC, Orientação a Objetos, PDO, Prepared Statements, Session Authentication, CSRF Protection)
- **Banco de Dados:** MySQL 8+ / MariaDB 10.4+ (InnoDB, UTF8mb4, Chaves Estrangeiras, Índices Compostos, CHECK Constraints)
- **Frontend:** HTML5, CSS3, JavaScript ES6+, Bootstrap 5.3, Bootstrap Icons, Chart.js
- **Gerenciador de Dependências:** Composer 2
- **Testes Automatizados:** PHPUnit 11 (Unitários e de Integração)
- **Análise Estática & Lint:** PHPStan (Nível 5) e PHP Lint (`php -l`)
- **Versionamento:** Git (Conventional Commits)

---

## 📋 Requisitos do Sistema

- PHP 8.2 ou superior com extensões `pdo_mysql`, `mbstring`, `json`, `ctype`, `xml`
- Composer 2.x
- Servidor MySQL 8+ ou MariaDB 10.4+ ativo na porta 3306

---

## ⚙️ Instalação e Execução

### 1. Clonar ou Acessar a Pasta do Projeto
```bash
cd mei-em-dia
```

### 2. Instalar Dependências
```bash
composer install
```

### 3. Configurar Variáveis de Ambiente
Copie o arquivo `.env.example` para `.env`:
```powershell
Copy-Item .env.example .env
```
*(Assegure-se de que `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` correspondam ao seu ambiente MySQL/MariaDB).*

### 4. Executar Migrações e Seeds do Banco de Dados
Crie as tabelas e insira os dados iniciais de demonstração:
```bash
composer migrate:seed
```

### 5. Iniciar o Servidor Local
```bash
php -S localhost:8000 -t public
```

### 6. Acessar no Navegador
- **Interface Web:** [http://localhost:8000](http://localhost:8000)
- **Health Check API:** [http://localhost:8000/api/health](http://localhost:8000/api/health)

**Acesso de Demonstração (Seed):**
- **E-mail:** `pedro@email.com`
- **Senha:** `SenhaSegura123`

---

## 🧪 Suíte de Testes Automatizados

O projeto conta com **79 testes automatizados** e **301 asserções**, cobrindo 100% dos fluxos críticos de negócio, integridade referencial, controle de concorrência e defesas de segurança.

Execute todos os testes:
```bash
composer test
```

Execute apenas testes unitários:
```bash
composer test:unit
```

Execute apenas testes de integração:
```bash
composer test:integration
```

Análise estática (PHPStan Nível 5):
```bash
composer analyse
```

Verificação sintática de todos os arquivos PHP:
```bash
composer lint
```

---

## 🔒 Arquitetura de Segurança

1. **Prevenção contra SQL Injection:**
   - 100% das consultas utilizam `PDO` com *Prepared Statements* parametrizados (`:placeholder`), sem interpolação de strings. `PDO::ATTR_EMULATE_PREPARES` configurado como `false`.
2. **Prevenção contra Cross-Site Scripting (XSS):**
   - Sanitização no servidor e escape estrito em HTML (`escapeHtml()` em todo output dinâmico no JS e `htmlspecialchars` nas views PHP).
3. **Prevenção contra Cross-Site Request Forgery (CSRF):**
   - Middleware `CsrfMiddleware` com validação de token criptográfico via cabeçalho `X-CSRF-Token` gerado por sessão.
4. **Armazenamento Seguro de Credenciais:**
   - Senhas criptografadas com `password_hash($senha, PASSWORD_BCRYPT)`. O hash nunca é exposto nas respostas da API.
5. **Prevenção contra Session Fixation & Hijacking:**
   - Regeneração de ID de sessão (`session_regenerate_id(true)`) no login e cadastro. Cookies de sessão configurados com `HttpOnly`, `SameSite=Lax` e `Secure` (em HTTPS).
6. **Proteção Multi-tenant (Isolamento por Negócio - Prevenção contra IDOR):**
   - Todas as operações em categorias e movimentações são amarradas estritamente ao `business_id` da sessão autenticada. Usuários não conseguem acessar dados de outras empresas.
7. **Controle Otimista de Concorrência:**
   - Campo `version` em movimentações financeiras para prevenir sobrescritas acidentais em acessos simultâneos (HTTP 409).

---

## 💡 Melhorias Implementadas a Partir do Feedback de Usuários

1. **Exportação de Movimentações em CSV (`GET /api/transactions/export`):**
   - Permite ao MEI exportar seu extrato financeiro filtrado com um clique no formato brasileiro (`.csv` com delimitador ponto-e-vírgula e codificação UTF-8 com BOM para Excel), facilitando o envio ao contador.
2. **Acompanhamento do Teto Anual MEI - DASN-SIMEI (`GET /api/dashboard/annual`):**
   - Barra de progresso visual em tempo real no Dashboard monitorando o faturamento acumulado no ano contra o teto oficial de R$ 81.000,00, com alertas de proximidade e excesso de limite.

---

## 📚 Endpoints da API REST

| Método | Rota | Autenticação | Descrição |
|---|---|---|---|
| `GET` | `/api/health` | Não | Status de saúde da aplicação |
| `GET` | `/api/auth/csrf` | Não | Obtenção do token CSRF |
| `POST` | `/api/auth/register` | Não | Cadastro de usuário, empresa e categorias |
| `POST` | `/api/auth/login` | Não | Login com sessão PHP |
| `POST` | `/api/auth/logout` | Sim | Encerramento de sessão |
| `GET` | `/api/auth/me` | Sim | Dados do usuário e empresa autenticados |
| `GET` | `/api/business` | Sim | Consulta dados cadastrais do negócio |
| `PATCH` | `/api/business` | Sim | Atualização de Razão Social e CNPJ |
| `GET` | `/api/categories` | Sim | Listagem de categorias (com filtro por tipo) |
| `POST` | `/api/categories` | Sim | Cadastro de categoria |
| `GET` | `/api/categories/{id}` | Sim | Detalhes de categoria |
| `PUT` | `/api/categories/{id}` | Sim | Atualização de categoria |
| `DELETE` | `/api/categories/{id}` | Sim | Exclusão de categoria (bloqueada se vinculada) |
| `GET` | `/api/transactions` | Sim | Listagem com filtros, busca e paginação |
| `GET` | `/api/transactions/export` | Sim | Exportação do extrato filtrado em CSV |
| `POST` | `/api/transactions` | Sim | Cadastro de receita/despesa |
| `GET` | `/api/transactions/{id}` | Sim | Detalhes de movimentação |
| `PUT` | `/api/transactions/{id}` | Sim | Atualização com controle de versão |
| `PATCH` | `/api/transactions/{id}/status` | Sim | Atualização rápida de status e pagamento |
| `DELETE` | `/api/transactions/{id}` | Sim | Exclusão de movimentação |
| `GET` | `/api/dashboard` | Sim | KPIs e gráficos do mês/ano |
| `GET` | `/api/dashboard/annual` | Sim | Faturamento acumulado e limite DASN-SIMEI |

---

## 📁 Estrutura de Diretórios

```text
mei-em-dia/
├── app/
│   ├── Controllers/       # Controladores REST e Web
│   ├── Core/              # Roteador, Request, Response, Database, Session
│   ├── Middleware/        # AuthMiddleware e CsrfMiddleware
│   ├── Models/            # User, Business, Category, Transaction
│   ├── Services/          # AuthService, BusinessService, CategoryService, TransactionService, DashboardService
│   └── Views/             # Interface HTML5/Bootstrap (index.php)
├── config/                # database.php
├── database/              # schema.sql, seeds.sql, migrate.php
├── docs/                  # RELATORIO_FINAL.md, APRESENTACAO.md
├── public/                # index.php, .htaccess, css/style.css, js/app.js
├── routes/                # api.php, web.php
├── storage/               # logs/.gitkeep
├── tests/
│   ├── Integration/       # AuthApiTest, BusinessApiTest, CategoryApiTest, TransactionApiTest, DashboardApiTest, SecurityTest, ImprovementFeaturesTest, WebControllerTest, DatabaseTest
│   └── Unit/              # RouterTest, AuthServiceTest, BusinessServiceTest, CategoryServiceTest, TransactionServiceTest, DashboardServiceTest
├── .env.example
├── .gitignore
├── composer.json
├── phpstan.neon
├── phpunit.xml
└── README.md
```
