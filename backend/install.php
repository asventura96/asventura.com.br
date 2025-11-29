<?php
// backend/install.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. CORREÇÃO DE CAMINHOS ---
if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
} else {
    die("❌ Erro Crítico: O arquivo <strong>config.php</strong> não foi encontrado.<br>Caminho atual: " . __DIR__);
}

echo "<h1>🛠️ Iniciando Instalação e Limpeza...</h1>";

try {
    // --- CRIAR TABELAS (RESUMIDO) ---
    // Mantendo a estrutura existente das tabelas principais...
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, image_url VARCHAR(255), link VARCHAR(255), created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS forms (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, recipient_email VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS form_fields (id INT AUTO_INCREMENT PRIMARY KEY, form_id INT NOT NULL, label VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, options TEXT NULL, placeholder VARCHAR(255) NULL, is_required BOOLEAN DEFAULT 0, order_index INT DEFAULT 999, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS form_submissions (id INT AUTO_INCREMENT PRIMARY KEY, form_id INT NOT NULL, data JSON NOT NULL, email_status VARCHAR(50) DEFAULT 'Pendente', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE)");
    
    // TABELA SETTINGS
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");
    echo "✅ Tabelas do banco de dados verificadas.<br>";

    // --- CONFIGURAÇÕES PADRÃO (LIMPAS) ---
    $defaults = [
        // Identidade
        'site_title' => 'Meu Site',
        'site_description' => '',
        
        // Contatos
        'contact_email' => '',
        'contact_phone' => '',
        'contact_address' => '',
        
        // Redes Sociais (NOVO: JSON Vazio)
        'social_links' => '[]',
        
        // Integrações (Mantive Recaptcha pois é útil para formulários públicos)
        'recaptcha_site_key' => '',
        'recaptcha_secret' => ''
    ];
    
    // Removemos configurações antigas de SMTP se existirem para não sujar o banco
    $keysToRemove = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'social_linkedin', 'social_instagram', 'social_github', 'social_whatsapp'];
    foreach ($keysToRemove as $key) {
        $pdo->prepare("DELETE FROM settings WHERE setting_key = ?")->execute([$key]);
    }
    echo "🧹 Configurações antigas (SMTP/Sociais individuais) limpas.<br>";
    
    // Insere os novos defaults
    $stmtCheck = $pdo->prepare("SELECT count(*) FROM settings WHERE setting_key = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");

    foreach ($defaults as $key => $val) {
        $stmtCheck->execute([$key]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute([$key, $val]);
            echo "➕ Configuração adicionada: $key<br>";
        }
    }
    echo "✅ Configurações padrão atualizadas.<br>";

    // --- ADMIN INICIAL (MANTIDO) ---
    $email = getenv('DEFAULT_ADMIN_EMAIL') ?: 'admin@admin.com';
    $pass  = getenv('DEFAULT_ADMIN_PASS')  ?: 'admin';
    $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() == 0) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)")->execute([$email, $hash]);
        echo "<hr>👤 Admin criado: $email / $pass<br>";
    }
    
    echo "<h2>🏁 Instalação Atualizada com Sucesso!</h2>";

} catch (PDOException $e) {
    echo "<hr>❌ <strong>ERRO:</strong> " . $e->getMessage();
}
?>