<?php
// backend/api/api_settings.php

// --- CORREÇÃO DE CAMINHO ---
if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
} else {
    // Fallback caso estrutura mude
    require '../config.php';
}

// CORS e Headers
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

try {
    $settings = [];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    
    // WHITELIST: Apenas o que o Frontend PÚBLICO precisa ver
    $publicKeys = [
        // Identidade
        'site_title', 'site_description', 'site_logo', 'site_favicon',
        
        // Contatos
        'contact_email', 'contact_phone', 'contact_address',
        
        // Redes Sociais (Agora é um JSON)
        'social_links',
        
        // Chaves Públicas de terceiros
        'recaptcha_site_key'
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['setting_key'], $publicKeys)) {
            $value = $row['setting_value'];
            
            // Transforma o JSON string em Array real para o Frontend
            if ($row['setting_key'] === 'social_links') {
                $decoded = json_decode($value);
                $settings[$row['setting_key']] = is_array($decoded) ? $decoded : [];
            } else {
                $settings[$row['setting_key']] = $value;
            }
        }
    }

    echo json_encode([
        "success" => true,
        "data" => $settings
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>