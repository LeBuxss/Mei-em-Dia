<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = (int) ($_ENV['DB_PORT'] ?? 3306);
$database = $_ENV['DB_DATABASE'] ?? 'mei_em_dia';
$username = (string) ($_ENV['DB_USERNAME'] ?? 'root');
$password = (string) ($_ENV['DB_PASSWORD'] ?? '');

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$fresh = in_array('--fresh', $argv, true);
$seed = in_array('--seed', $argv, true);

echo "Iniciando migração do banco de dados...\n";

try {
    // 1. Conecta ao servidor MySQL
    $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($serverDsn, $username, $password, $options);

    if ($fresh) {
        echo "Opção --fresh detectada. Recriando banco '{$database}'...\n";
        $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
    }

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[x] Banco '{$database}' verificado/criado com sucesso.\n";

    // 2. Conecta ao banco 'mei_em_dia'
    $dbDsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $dbPdo = new PDO($dbDsn, $username, $password, $options);

    // 3. Executa schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("Arquivo de schema não encontrado: {$schemaFile}");
    }

    $schemaSql = (string) file_get_contents($schemaFile);
    $dbPdo->exec($schemaSql);
    echo "[x] Tabelas criadas com sucesso (users, businesses, categories, transactions).\n";

    // 4. Executa seeds.sql se solicitado
    if ($seed) {
        $seedFile = __DIR__ . '/seeds.sql';
        if (!file_exists($seedFile)) {
            throw new RuntimeException("Arquivo de seeds não encontrado: {$seedFile}");
        }

        $seedSql = (string) file_get_contents($seedFile);
        $dbPdo->exec($seedSql);
        echo "[x] Dados de demonstração (seeds) inseridos com sucesso.\n";
    }

    echo "Migração concluída com sucesso!\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Erro na migração: " . $e->getMessage() . "\n");
    exit(1);
}
