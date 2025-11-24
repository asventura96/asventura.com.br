<?php
require_once __DIR__ . '/../config.php';

// Envia cabeçalhos CORS
send_cors_headers();

// Lida com a requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Lida com a requisição GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Busca todos os projetos
        $stmt = $pdo->query('SELECT id, title, description, image_url, link FROM projects ORDER BY created_at DESC');
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retorna os projetos em formato JSON
        json_response(['success' => true, 'data' => $projects]);

    } catch (PDOException $e) {
        // Em caso de erro no banco de dados
        json_response(['success' => false, 'message' => 'Erro ao buscar projetos: ' . $e->getMessage()], 500);
    }
}

// Lida com métodos não permitidos
json_response(['success' => false, 'message' => 'Método não permitido.'], 405);

?>
