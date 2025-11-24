<?php
// backend/config.php

// 1. Definição manual do caminho do arquivo .env
// __DIR__ pega a pasta atual (backend ou test).
$envPath = __DIR__ . '/.env';

// Variáveis padrão para não quebrar com "undefined"
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';

// 2. Leitura Forçada do Arquivo
if (file_exists($envPath)) {
    // Lê o arquivo linha por linha
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignora comentários (#)
        if (strpos(trim($line), '#') === 0) continue;
        
        // Quebra no sinal de igual
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove aspas se houver
            $value = trim($value, '"\'');

            // Atribui diretamente às variáveis
            switch ($name) {
                case 'DB_HOST': $db_host = $value; break;
                case 'DB_NAME': $db_name = $value; break;
                case 'DB_USER': $db_user = $value; break;
                case 'DB_PASS': $db_pass = $value; break;
                case 'SMTP_HOST': define('SMTP_HOST', $value); break;
                case 'SMTP_PORT': define('SMTP_PORT', $value); break;
                case 'SMTP_USER': define('SMTP_USER', $value); break;
                case 'SMTP_PASS': define('SMTP_PASS', $value); break;
            }
        }
    }
} else {
    // Se não achar o arquivo, para tudo para você saber o motivo
    header('Content-Type: application/json');
    die(json_encode(["erro" => "Arquivo .env não encontrado no caminho: " . $envPath]));
}

// 3. Verifica se pegou os dados
if (empty($db_user)) {
    header('Content-Type: application/json');
    die(json_encode(["erro" => "Arquivo .env foi lido, mas as variáveis (DB_USER) estão vazias ou mal formatadas."]));
}

// --- CONFIGURAÇÕES GERAIS ---
define('CORS_ORIGIN', '*');

// --- INICIALIZAÇÃO DO BANCO ---
function init_db() {
    global $db_host, $db_name, $db_user, $db_pass;

    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;

    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        // Mostra o erro real
        echo json_encode(["erro" => "Falha no Banco: " . $e->getMessage()]);
        exit;
    }
}

// Inicializa a conexão
$pdo = init_db();

// --- FUNÇÕES AUXILIARES ---
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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(["erro" => "Não autorizado."]);
        exit;
    }
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>