<?php
require 'auth.php';
require '../config.php';

$msg = "";

// 1. CRIAR FORMULÁRIO NOVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = $_POST['title'];
    $slug = $_POST['slug']; // ID único (ex: contato-home)
    $email = $_POST['recipient_email'];

    if (!empty($title) && !empty($slug) && !empty($email)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO forms (title, slug, recipient_email) VALUES (?, ?, ?)");
            $stmt->execute([$title, $slug, $email]);
            $msg = "✅ Formulário criado!";
        } catch (Exception $e) {
            $msg = "❌ Erro (Slug já existe?): " . $e->getMessage();
        }
    }
}

// 2. LISTAR OS EXISTENTES
$forms = $pdo->query("SELECT * FROM forms ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Formulários</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">
    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">📝 Meus Formulários</h1>
        <a href="index.php" class="text-blue-600 hover:underline">← Voltar</a>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl">
        <?php if($msg): ?><div class="bg-blue-100 p-3 mb-4 rounded"><?php echo $msg; ?></div><?php endif; ?>

        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="font-bold mb-4">Novo Formulário</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="create">
                <input type="text" name="title" placeholder="Nome (ex: Contato Home)" required class="border p-2 rounded">
                <input type="text" name="slug" placeholder="Slug (ex: contact-home)" required class="border p-2 rounded">
                <input type="email" name="recipient_email" placeholder="Email de destino" required class="border p-2 rounded">
                <button type="submit" class="bg-green-600 text-white font-bold p-2 rounded hover:bg-green-700 md:col-span-3">Criar</button>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left">Nome</th>
                        <th class="p-3 text-left">Slug</th>
                        <th class="p-3 text-left">Destino</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($forms as $f): ?>
                    <tr class="border-t">
                        <td class="p-3 font-bold"><?php echo $f['title']; ?></td>
                        <td class="p-3 text-blue-600 font-mono text-sm"><?php echo $f['slug']; ?></td>
                        <td class="p-3"><?php echo $f['recipient_email']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>