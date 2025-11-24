<?php
require 'auth.php';
require '../config.php';

// Pega o ID do formulário que vamos editar
$form_id = $_GET['form_id'] ?? null;
$msg = "";

if (!$form_id) {
    header("Location: forms.php");
    exit;
}

// Busca o formulário para mostrar o nome no título
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

// --- 1. ADICIONAR NOVO CAMPO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_field') {
    $label = $_POST['label']; // Ex: "Seu Whatsapp"
    // Cria um nome técnico automático (ex: "seu_whatsapp")
    $name = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $label)));
    $type = $_POST['type']; // text, email, textarea...
    $options = $_POST['options']; // Para listas suspensas
    $required = isset($_POST['required']) ? 1 : 0;

    if (!empty($label)) {
        $sql = "INSERT INTO form_fields (form_id, label, name, type, options, is_required) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$form_id, $label, $name, $type, $options, $required]);
        $msg = "✅ Campo adicionado!";
    }
}

// --- 2. EXCLUIR CAMPO ---
if (isset($_GET['delete_field'])) {
    $field_id = $_GET['delete_field'];
    $stmt = $pdo->prepare("DELETE FROM form_fields WHERE id = ? AND form_id = ?");
    $stmt->execute([$field_id, $form_id]);
    header("Location: fields.php?form_id=$form_id");
    exit;
}

// --- 3. LISTAR CAMPOS ATUAIS ---
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Campos - <?php echo $form['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">

    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <div>
            <span class="text-xs text-gray-500 uppercase">Editando:</span>
            <h1 class="text-xl font-bold text-gray-800"><?php echo $form['title']; ?></h1>
        </div>
        <a href="forms.php" class="text-blue-600 hover:underline">← Voltar</a>
    </nav>

    <div class="container mx-auto p-4 max-w-5xl grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded shadow">
                <h2 class="font-bold mb-4 border-b pb-2">Novo Campo</h2>
                <?php if($msg): ?><div class="bg-green-100 p-2 mb-2 text-sm rounded"><?php echo $msg; ?></div><?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_field">
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Nome do Campo (Label)</label>
                        <input type="text" name="label" placeholder="Ex: Telefone" required class="w-full p-2 border rounded">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Tipo</label>
                        <select name="type" class="w-full p-2 border rounded bg-white">
                            <option value="text">Texto Curto</option>
                            <option value="email">E-mail</option>
                            <option value="tel">Telefone / Zap</option>
                            <option value="textarea">Texto Longo (Mensagem)</option>
                            <option value="select">Lista (Select)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Opções (Só para Lista)</label>
                        <input type="text" name="options" placeholder="Ex: Orçamento, Dúvida" class="w-full p-2 border rounded">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="required" id="req" value="1" checked>
                        <label for="req" class="text-sm">Obrigatório?</label>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700">
                        + Adicionar
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <h2 class="font-bold mb-4 text-lg">Campos do Formulário</h2>
            <div class="bg-white rounded shadow p-6 space-y-4">
                <?php if(empty($fields)): ?>
                    <p class="text-gray-400 text-center">Nenhum campo criado ainda.</p>
                <?php endif; ?>

                <?php foreach($fields as $field): ?>
                    <div class="border p-3 rounded relative hover:bg-gray-50 flex justify-between items-center">
                        <div>
                            <strong class="text-gray-800"><?php echo $field['label']; ?></strong>
                            <span class="text-xs bg-gray-200 px-1 rounded ml-2"><?php echo $field['type']; ?></span>
                            <?php if($field['is_required']): ?>
                                <span class="text-red-500 text-xs font-bold">*Obrigatório</span>
                            <?php endif; ?>
                        </div>
                        <a href="?form_id=<?php echo $form_id; ?>&delete_field=<?php echo $field['id']; ?>" 
                           onclick="return confirm('Apagar este campo?')"
                           class="text-red-500 hover:font-bold text-sm">
                           Excluir
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</body>
</html>