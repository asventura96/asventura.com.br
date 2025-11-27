<?php
// backend/api_form.php

// Ativa exibição de erros (útil em dev, desativar em prod)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'config.php';

// 1. Configuração de CORS (Essencial para Next.js falar com PHP)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

// 2. Validação do Slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(["error" => "Slug não informado."]);
    exit;
}

try {
    // 3. Buscar o Formulário
    $stmt = $pdo->prepare("SELECT id, title, recipient_email FROM forms WHERE slug = ?");
    $stmt->execute([$slug]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        http_response_code(404);
        echo json_encode(["error" => "Formulário não encontrado."]);
        exit;
    }

    // 4. Buscar Campos (CORRIGIDO)
    // Mudanças:
    // - Adicionado campo 'placeholder' no SELECT
    // - Mudado ORDER BY para 'order_index ASC' (para respeitar o drag-and-drop)
    $stmtFields = $pdo->prepare("
        SELECT id, label, name, type, options, is_required, placeholder 
        FROM form_fields 
        WHERE form_id = ? 
        ORDER BY order_index ASC
    ");
    $stmtFields->execute([$form['id']]);
    $fields = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

    // 5. Processamento de Dados para o Frontend
    foreach ($fields as &$field) {
        // Converte JSON string do banco para Array PHP real
        // O frontend receberá um array limpo ["Opção 1", "Opção 2"], não uma string.
        $decodedOptions = json_decode($field['options'] ?? '[]');
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOptions)) {
            $field['options'] = $decodedOptions;
        } else {
            // Fallback para legado (se houver dados antigos separados por vírgula)
            $field['options'] = !empty($field['options']) ? explode(',', $field['options']) : [];
        }

        // Garante tipagem correta para o React
        $field['is_required'] = (bool)$field['is_required'];
        $field['placeholder'] = $field['placeholder'] ?? ''; // Garante string vazia se null
    }
    // Remove a referência & para evitar bugs futuros no loop
    unset($field);

    // 6. Retorno JSON
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "form" => [
            "id" => $form['id'],
            "title" => $form['title'],
            // Não envie recipient_email para o front por segurança!
        ],
        "fields" => $fields
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro interno no servidor."]);
}
?>