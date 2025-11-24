<?php

// Configuração do Banco de Dados (SQLite para simplicidade no desenvolvimento)
// Em produção na Hostinger, você usaria MySQL/MariaDB
define('DB_PATH', __DIR__ . '/database.sqlite');

// Configuração de Segurança
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'senha_segura_aqui'); // Mudar em produção!

// Configuração de SMTP (para o formulário de contato)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@asventura.com.br');
define('SMTP_PASS', 'sua_senha_smtp');
define('SMTP_FROM_EMAIL', 'contato@asventura.com.br');
define('SMTP_FROM_NAME', 'André Ventura - Contato');

// Configuração de CORS para a API (permitir acesso do Next.js)
define('CORS_ORIGIN', 'http://localhost:3000'); // Mudar para o domínio do Next.js em produção

// Função para inicializar o banco de dados
function init_db() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tabela de Projetos
        $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            image_url TEXT,
            link TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Tabela de Envios de Contato
        $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'Novo', -- Novo, Em Progresso, Respondido
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        return $pdo;
    } catch (PDOException $e) {
        die("Erro ao conectar/inicializar o banco de dados: " . $e->getMessage());
    }
}

// Inicializa o banco de dados
$pdo = init_db();

// Função para enviar cabeçalhos CORS
function send_cors_headers() {
    header("Access-Control-Allow-Origin: " . CORS_ORIGIN);
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
}

// Função para autenticação básica
function authenticate() {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
        $_SERVER['PHP_AUTH_USER'] !== ADMIN_USER || $_SERVER['PHP_AUTH_PW'] !== ADMIN_PASS) {
        header('WWW-Authenticate: Basic realm="Painel Administrativo"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Acesso não autorizado.';
        exit;
    }
}

// Função para enviar resposta JSON
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Incluir o autoload do Composer para usar o PHPMailer
require __DIR__ . '/vendor/autoload.php';
// require 'vendor/autoload.php';

?>
