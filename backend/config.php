<?php

// --- 1. DADOS DO BANCO DE DADOS ---
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

// --- 3. SMTP (Para enviar e-mail) ---
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL'));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME'));

// --- 4. CORS (Para o Next.js não ser bloqueado) ---
define('CORS_ORIGIN', '*');

// --- 5. INICIALIZAÇÃO DO BANCO ---
function init_db() {
    try {
        // Conexão MySQL
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Criação de Tabelas (Sintaxe corrigida para MySQL)
        // MySQL usa AUTO_INCREMENT, SQLite usa AUTOINCREMENT. Se não mudar, dá erro.
        
        // Tabela de Projetos
        $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            link VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Tabela de Contatos
        $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(50) DEFAULT 'Novo',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        return $pdo;

    } catch (PDOException $e) {
        // Retorna erro JSON se falhar, pra você ver no navegador
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(["erro" => "Falha no Banco: " . $e->getMessage()]);
        exit;
    }
}

// Inicializa a conexão
$pdo = init_db();

// --- 6. FUNÇÕES AUXILIARES ---

function send_cors_headers() {
    header("Access-Control-Allow-Origin: " . CORS_ORIGIN);
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function authenticate() {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
        $_SERVER['PHP_AUTH_USER'] !== ADMIN_USER || $_SERVER['PHP_AUTH_PW'] !== ADMIN_PASS) {
        header('WWW-Authenticate: Basic realm="Painel Administrativo"');
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(["erro" => "Acesso negado"]);
        exit;
    }
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Tenta carregar o Composer (se existir)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}
?>