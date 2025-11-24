<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Lógica para marcar como "Lido" ou "Respondido"
if ($action === 'mark_read' && $id) {
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET status = 'Lido' WHERE id = ? AND status = 'Novo'");
        $stmt->execute([$id]);
        $message = 'Contato marcado como Lido.';
        $action = 'list';
    } catch (PDOException $e) {
        $message = 'Erro ao marcar contato: ' . $e->getMessage();
    }
} elseif ($action === 'mark_replied' && $id) {
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET status = 'Respondido' WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Contato marcado como Respondido.';
        $action = 'list';
    } catch (PDOException $e) {
        $message = 'Erro ao marcar contato: ' . $e->getMessage();
    }
}

// Lógica de Visualização
$contacts = [];
if ($action === 'list') {
    $contacts = $pdo->query('SELECT * FROM contacts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Contatos - Painel Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }
        .header { background-color: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { padding: 20px; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-novo { background-color: #ffdddd; font-weight: bold; }
        .status-respondido { background-color: #ddffdd; }
        .actions a { margin-right: 10px; text-decoration: none; color: #007bff; }
        .actions a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gerenciar Contatos</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="projects.php">Projetos</a>
            <a href="logout.php">Sair</a>
        </div>
    </div>
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Lista de Envios de Contato</h2>
        <?php if (count($contacts) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Mensagem</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <tr class="status-<?php echo strtolower($contact['status']); ?>">
                            <td><?php echo $contact['id']; ?></td>
                            <td><?php echo htmlspecialchars($contact['name']); ?></td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($contact['message'], 0, 50))) . (strlen($contact['message']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo $contact['status']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?></td>
                            <td class="actions">
                                <?php if ($contact['status'] === 'Novo'): ?>
                                    <a href="?action=mark_read&id=<?php echo $contact['id']; ?>">Marcar como Lido</a>
                                <?php endif; ?>
                                <?php if ($contact['status'] !== 'Respondido'): ?>
                                    <a href="?action=mark_replied&id=<?php echo $contact['id']; ?>">Marcar como Respondido</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum contato recebido ainda.</p>
        <?php endif; ?>
    </div>
</body>
</html>
