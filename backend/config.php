<?php
// backend/config.php

// 1. FORÇAR HORÁRIO DE BRASÍLIA (Resolve o problema das 3 horas de diferença)
date_default_timezone_set('America/Sao_Paulo');

// Tenta ler variáveis de ambiente
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$smtp_host = getenv('SMTP_HOST');

// Se não achou no ambiente (Hostinger), tenta ler o arquivo .env
if (!$db_user) {
    $envPath = __DIR__ . '/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, '"\' '); // Limpa espaços e aspas
                
                // Define variável
                putenv("$name=$value");
                
                // Preenche variáveis locais
                if ($name == 'DB_HOST') $db_host = $value;
                if ($name == 'DB_NAME') $db_name = $value;
                if ($name == 'DB_USER') $db_user = $value;
                if ($name == 'DB_PASS') $db_pass = $value;
                if ($name == 'SMTP_HOST') $smtp_host = $value;
            }
        }
    }
}

// Definições Gerais
if (!defined('SMTP_HOST') && $smtp_host) {
    define('SMTP_HOST', getenv('SMTP_HOST'));
    define('SMTP_PORT', getenv('SMTP_PORT'));
    define('SMTP_USER', getenv('SMTP_USER'));
    define('SMTP_PASS', getenv('SMTP_PASS'));
}
define('CORS_ORIGIN', '*');

if (empty($db_user)) {
    header('Content-Type: application/json');
    die(json_encode(["erro" => "Credenciais de banco não encontradas."]));
}

// Conexão Banco
function init_db() {
    global $db_host, $db_name, $db_user, $db_pass;
    $host = $db_host ?: 'localhost';

    try {
        $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Garante que o MySQL também entenda que é horário do Brasil
        $pdo->exec("SET time_zone = '-03:00'");

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["erro" => "Falha no Banco: " . $e->getMessage()]);
        exit;
    }
}

$pdo = init_db();

// Funções Auxiliares
function send_cors_headers() {
    header("Access-Control-Allow-Origin: " . CORS_ORIGIN);
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function authenticate() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(["erro" => "Não autorizado."]);
        exit;
    }
}
?>