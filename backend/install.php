<?php
// Ativa exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

echo "<h1>Iniciando Instalação do Banco de Dados...</h1>";

try {
    // --- 1. CRIAR TABELA DE ADMINS ---
    $sqlAdmins = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlAdmins);
    echo "✅ Tabela 'admins' verificada.<br>";

    // --- 2. CRIAR TABELA DE PROJETOS ---
    $sqlProjects = "CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        link VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlProjects);
    echo "✅ Tabela 'projects' verificada.<br>";

    // --- 3. CRIAR TABELA DE CONTATOS ---
    $sqlContacts = "CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'Novo',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlContacts);
    echo "✅ Tabela 'contacts' verificada.<br>";

    // --- 4. CRIAR TABELA DE FORMULÁRIOS (PAI) ---
    // Esta tabela PRECISAVA vir antes das outras
    $sqlForms = "CREATE TABLE IF NOT EXISTS forms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        recipient_email VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlForms);
    echo "✅ Tabela 'forms' verificada.<br>";

    // --- 5. CRIAR TABELA DE CAMPOS DO FORMULÁRIO ---
    // Corrigido: Removi as aspas duplas dos comentários para não quebrar o PHP
    $sqlFields = "CREATE TABLE IF NOT EXISTS form_fields (
        id INT AUTO_INCREMENT PRIMARY KEY,
        form_id INT NOT NULL,
        label VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        options TEXT NULL,
        is_required BOOLEAN DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlFields);
    echo "✅ Tabela 'form_fields' verificada.<br>";

    // --- 6. CRIAR TABELA DE ENVIOS (RESPOSTAS) ---
    $sqlSubmissions = "CREATE TABLE IF NOT EXISTS form_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        form_id INT NOT NULL,
        data JSON NOT NULL,
        email_status VARCHAR(50) DEFAULT 'Pendente',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlSubmissions);
    echo "✅ Tabela 'form_submissions' verificada.<br>";

    // --- 7. CRIAR O ADMIN INICIAL ---
    $email = getenv('DEFAULT_ADMIN_EMAIL') ?: 'admin@admin.com';
    $pass  = getenv('DEFAULT_ADMIN_PASS')  ?: 'admin';

    $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() == 0) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
        $insert->execute([$email, $hash]);
        
        echo "<hr>✅ <strong>SUCESSO:</strong> Usuário Admin criado!<br>";
        echo "Email: $email<br>";
    } else {
        echo "<hr>ℹ️ O usuário admin já existe.<br>";
    }

    echo "<h2>🏁 Instalação Concluída!</h2>";

} catch (PDOException $e) {
    echo "<hr>❌ <strong>ERRO CRÍTICO:</strong> " . $e->getMessage();
}
?>