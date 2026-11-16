# MEI em Dia

Sistema web de gestão financeira simplificada para Microempreendedores Individuais (MEIs).

## Objetivo

Centralizar o controle de receitas, despesas, vencimentos e indicadores financeiros de pequenos negócios.

## Stack

- PHP 8.2+
- Composer
- MySQL 8+ (a conexão será implementada na Tarefa 1)
- PHPUnit 11
- HTML, CSS, JavaScript e Bootstrap 5 (interface nas tarefas posteriores)

## Requisitos

- PHP 8.2 ou superior
- Composer 2
- Extensões PHP `mbstring`, `xml`, `ctype` e `json`
- MySQL 8+ será necessário a partir da Tarefa 1

## Instalação

1. Clone o repositório.
2. Instale as dependências:

   ```bash
   composer install
   ```

3. Copie `.env.example` para `.env` e ajuste as configurações locais:

   ```bash
   cp .env.example .env
   ```

   No Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

4. Inicie o servidor local apontando a raiz pública:

   ```bash
   php -S localhost:8000 -t public
   ```

5. Acesse `http://localhost:8000/api/health`.

Resposta esperada: JSON com `success: true` e `status: ok`.

## Testes

Execute todos os testes:

```bash
composer test
```

Execute somente os testes unitários:

```bash
composer test:unit
```

## Estrutura

```text
mei-em-dia/
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Middleware/
│   ├── Models/
│   ├── Services/
│   └── Views/
├── config/
├── database/
├── public/
│   ├── .htaccess
│   └── index.php
├── routes/
├── storage/
│   └── logs/
├── tests/
│   └── Unit/
├── .env.example
├── .gitignore
├── composer.json
├── phpunit.xml
└── README.md
```

## Segurança e configuração

- Nunca versione o arquivo `.env`.
- Não armazene senhas em texto puro.
- Não exponha detalhes de exceções em produção (`APP_DEBUG=false`).
- As próximas tarefas implementarão sessão, CSRF, autorização e acesso ao banco via PDO.

## Escopo desta entrega

Esta é a estrutura inicial (Tarefa 0). O banco de dados, autenticação, CRUDs e dashboard serão desenvolvidos nas tarefas seguintes.
