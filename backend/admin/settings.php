<?php
require 'auth.php';
require '../config.php';

$msg = "";

// --- 1. SALVAR CONFIGURAÇÕES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    try {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        // Lista de campos permitidos para salvar
        $fields = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'recaptcha_site_key', 'recaptcha_secret'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                // Se o campo existe no POST, atualiza no banco
                $stmt->execute([$_POST[$field], $field]);
            }
        }
        $msg = "✅ Configurações salvas com sucesso!";
    } catch (Exception $e) {
        $msg = "❌ Erro ao salvar: " . $e->getMessage();
    }
}

// --- 2. BUSCAR CONFIGURAÇÕES DO BANCO ---
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Se der erro (tabela não existe), segue com array vazio
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">

    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">⚙️ Configurações</h1>
        <a href="index.php" class="text-blue-600 hover:underline">← Voltar ao Painel</a>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl">
        
        <?php if($msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 p-4 mb-6 rounded">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <input type="hidden" name="action" value="save">

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                <h2 class="font-bold text-lg mb-4 text-gray-800 flex items-center gap-2">
                    📧 Configuração de E-mail (SMTP)
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Host (Servidor)</label>
                        <input type="text" name="smtp_host" 
                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" 
                               placeholder="smtp.hostinger.com"
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Porta</label>
                        <input type="text" name="smtp_port" 
                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Usuário (E-mail)</label>
                        <input type="email" name="smtp_user" id="smtp_user"
                               value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" 
                               placeholder="contato@seu-dominio.com"
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Senha</label>
                        <input type="password" name="smtp_pass" 
                               value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center gap-4">
                    <button type="button" onclick="testarEmail()" 
                            class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-black transition font-bold text-sm w-full sm:w-auto">
                        🧪 Testar Envio
                    </button>
                    <span id="test-result" class="text-sm font-medium"></span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-orange-500">
                <h2 class="font-bold text-lg mb-4 text-gray-800 flex items-center gap-2">
                    🛡️ Google reCAPTCHA (v2 - Checkbox)
                </h2>
                <p class="text-xs text-gray-500 mb-4">
                    Para proteger seu site de robôs. Gere as chaves no <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-blue-600 underline">Google Admin</a>.
                </p>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Site Key (Chave do Site)</label>
                        <input type="text" name="recaptcha_site_key" 
                               value="<?php echo htmlspecialchars($settings['recaptcha_site_key'] ?? ''); ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 font-mono text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Secret Key (Chave Secreta)</label>
                        <input type="text" name="recaptcha_secret" 
                               value="<?php echo htmlspecialchars($settings['recaptcha_secret'] ?? ''); ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 font-mono text-sm">
                    </div>
                </div>
            </div>

            <div class="text-right pb-10">
                <button type="submit" class="bg-green-600 text-white font-bold py-3 px-8 rounded shadow hover:bg-green-700 transition transform hover:scale-105">
                    💾 Salvar Todas as Configurações
                </button>
            </div>
        </form>
    </div>

    <script>
    async function testarEmail() {
        const btn = document.querySelector('button[onclick="testarEmail()"]');
        const result = document.getElementById('test-result');
        const email = document.getElementById('smtp_user').value;

        if(!email) { 
            alert('Por favor, preencha e SALVE o e-mail de usuário antes de testar.'); 
            return; 
        }

        btn.disabled = true;
        btn.textContent = "Enviando teste...";
        btn.classList.add("opacity-50");
        result.textContent = "";
        result.className = "text-sm font-medium";

        try {
            // Chama o arquivo que vamos criar no próximo passo
            const res = await fetch('test_smtp_api.php');
            const json = await res.json();

            if(json.success) {
                result.textContent = "✅ " + json.message;
                result.classList.add("text-green-600");
            } else {
                result.textContent = "❌ " + json.message;
                result.classList.add("text-red-600");
            }
        } catch (e) {
            result.textContent = "❌ Erro de conexão com o servidor de teste.";
            result.classList.add("text-red-600");
        } finally {
            btn.disabled = false;
            btn.textContent = "🧪 Testar Envio";
            btn.classList.remove("opacity-50");
        }
    }
    </script>
</body>
</html>