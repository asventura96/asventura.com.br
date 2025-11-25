<?php
// backend/admin/submissions.php

require 'auth.php';
require '../config.php';

// 1. Validação do ID
$form_id = $_GET['form_id'] ?? null;

if (!$form_id) {
    header("Location: forms.php");
    exit;
}

// 2. Busca informações do formulário (só pra mostrar o título)
$stmt = $pdo->prepare("SELECT title FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

if (!$form) {
    die("Formulário não encontrado.");
}

// 3. Busca as mensagens (Submissões)
$stmtSub = $pdo->prepare("SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC");
$stmtSub->execute([$form_id]);
$submissions = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Mensagens - <?php echo htmlspecialchars($form['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">

    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <div>
            <span class="text-xs text-gray-500 uppercase">Mensagens recebidas:</span>
            <h1 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($form['title']); ?></h1>
        </div>
        <a href="forms.php" class="text-blue-600 hover:underline">← Voltar</a>
    </nav>

    <div class="container mx-auto p-4 max-w-3xl">
        
        <div class="mb-4 text-gray-500 text-sm text-right">
            Total: <strong><?php echo count($submissions); ?></strong> mensagens
        </div>

        <?php if(empty($submissions)): ?>
            <div class="bg-white p-10 rounded shadow text-center text-gray-400">
                <p class="text-xl mb-2">📭 Nenhuma mensagem ainda.</p>
                <p class="text-sm">Preencha o formulário no site para testar.</p>
            </div>
        <?php else: ?>
            
            <div class="space-y-4">
                <?php foreach($submissions as $sub): 
                    // AQUI A MÁGICA: Transforma o texto JSON em Array PHP
                    $data = json_decode($sub['data'], true);
                ?>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 <?php echo $sub['email_status'] == 'Enviado' ? 'border-green-500' : 'border-red-500'; ?> hover:shadow-md transition">
                        
                        <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-500 font-mono">
                                📅 <?php echo date('d/m/Y H:i', strtotime($sub['created_at'])); ?>
                            </span>
                            
                            <span class="text-xs font-bold px-2 py-1 rounded <?php echo $sub['email_status'] == 'Enviado' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                Email: <?php echo $sub['email_status']; ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <?php if (is_array($data)): ?>
                                <?php foreach($data as $key => $value): ?>
                                    <div>
                                        <strong class="block text-xs text-gray-400 uppercase tracking-wide mb-1">
                                            <?php echo str_replace('_', ' ', $key); // Ex: seu_nome -> seu nome ?>
                                        </strong>
                                        <div class="text-gray-800 whitespace-pre-wrap bg-gray-50 p-2 rounded border border-gray-100">
                                            <?php echo nl2br(htmlspecialchars($value)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-red-500">Erro ao ler dados (JSON inválido).</p>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>