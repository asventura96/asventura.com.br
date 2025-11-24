<?php
session_start();
require_once __DIR__ . '/../config.php';

// Se já estiver logado, joga pro painel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Se enviou o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega os dados do form
    $email = $_POST['email'] ?? ''; // Atenção: O name no HTML abaixo deve ser 'email'
    $password = $_POST['password'] ?? '';

    try {
        // BUSCA NO BANCO DE DADOS (Não usa mais ADMIN_USER)
        $stmt = $pdo->prepare("SELECT id, email, password FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se achou o usuário E se a senha bate
        if ($user && password_verify($password, $user['password'])) {
            // SUCESSO!
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'E-mail ou senha incorretos.';
        }
    } catch (PDOException $e) {
        $error = "Erro ao conectar no banco.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Painel Admin</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                <input type="email" name="email" required 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Senha</label>
                <input type="password" name="password" required 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                Entrar
            </button>
        </form>
    </div>

</body>
</html>