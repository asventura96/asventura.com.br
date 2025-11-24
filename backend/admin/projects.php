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

// Lógica de Ações (Adicionar, Editar, Excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $link = $_POST['link'] ?? '';

    if ($action === 'add') {
        try {
            $stmt = $pdo->prepare('INSERT INTO projects (title, description, image_url, link) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $description, $image_url, $link]);
            $message = 'Projeto adicionado com sucesso!';
            $action = 'list';
        } catch (PDOException $e) {
            $message = 'Erro ao adicionar projeto: ' . $e->getMessage();
        }
    } elseif ($action === 'edit' && $id) {
        try {
            $stmt = $pdo->prepare('UPDATE projects SET title = ?, description = ?, image_url = ?, link = ? WHERE id = ?');
            $stmt->execute([$title, $description, $image_url, $link, $id]);
            $message = 'Projeto atualizado com sucesso!';
            $action = 'list';
        } catch (PDOException $e) {
            $message = 'Erro ao atualizar projeto: ' . $e->getMessage();
        }
    }
} elseif ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Projeto excluído com sucesso!';
        $action = 'list';
    } catch (PDOException $e) {
        $message = 'Erro ao excluir projeto: ' . $e->getMessage();
    }
}

// Lógica de Visualização
$projects = [];
$project_data = ['title' => '', 'description' => '', 'image_url' => '', 'link' => ''];

if ($action === 'list') {
    $projects = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'edit' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    $project_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project_data) {
        $message = 'Projeto não encontrado.';
        $action = 'list';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Projetos - Painel Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }
        .header { background-color: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { padding: 20px; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a { margin-right: 10px; text-decoration: none; color: #007bff; }
        .actions a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gerenciar Projetos</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="contacts.php">Contatos</a>
            <a href="logout.php">Sair</a>
        </div>
    </div>
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <h2>Lista de Projetos</h2>
            <p><a href="?action=add"><button>+ Adicionar Novo Projeto</button></a></p>
            <?php if (count($projects) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Link</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo $project['id']; ?></td>
                                <td><?php echo htmlspecialchars($project['title']); ?></td>
                                <td><a href="<?php echo htmlspecialchars($project['link']); ?>" target="_blank"><?php echo htmlspecialchars($project['link']); ?></a></td>
                                <td class="actions">
                                    <a href="?action=edit&id=<?php echo $project['id']; ?>">Editar</a>
                                    <a href="?action=delete&id=<?php echo $project['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este projeto?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum projeto cadastrado ainda.</p>
            <?php endif; ?>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <h2><?php echo $action === 'add' ? 'Adicionar Novo Projeto' : 'Editar Projeto'; ?></h2>
            <form method="POST">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="title">Título:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($project_data['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Descrição:</label>
                    <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($project_data['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image_url">URL da Imagem (Ex: /images/projeto.jpg):</label>
                    <input type="text" id="image_url" name="image_url" value="<?php echo htmlspecialchars($project_data['image_url']); ?>">
                </div>
                <div class="form-group">
                    <label for="link">Link do Projeto (URL):</label>
                    <input type="text" id="link" name="link" value="<?php echo htmlspecialchars($project_data['link']); ?>">
                </div>
                <button type="submit"><?php echo $action === 'add' ? 'Adicionar' : 'Salvar Alterações'; ?></button>
                <a href="projects.php"><button type="button">Cancelar</button></a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
