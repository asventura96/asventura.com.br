<?php
// backend/admin/users.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'auth.php';
require '../config.php';

// --- API HANDLING (JSON POST) ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $action = $input['action'] ?? '';

        // 1. CRIAR NOVO USUÁRIO
        if ($action === 'create') {
            $email = trim($input['email']);
            $password = $input['password'];

            if (empty($email) || empty($password)) {
                throw new Exception("E-mail e senha são obrigatórios.");
            }

            // Verifica duplicidade
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Este e-mail já está cadastrado.");
            }

            // Hash e Insert
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
            $stmt->execute([$email, $hash]);

            $response['success'] = true;
            $response['message'] = "Administrador adicionado!";
        }

        // 2. EXCLUIR USUÁRIO
        if ($action === 'delete') {
            $id = $input['id'];
            
            // Trava de segurança no Backend
            if ($id == $_SESSION['admin_id']) {
                throw new Exception("Você não pode excluir sua própria conta.");
            }

            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);

            $response['success'] = true;
            $response['message'] = "Usuário removido com sucesso.";
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// --- VIEW (GET) ---
$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Time</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans"
      x-data="usersManager(<?php echo htmlspecialchars(json_encode($admins)); ?>, <?php echo $_SESSION['admin_id']; ?>)">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-teal-600">
                👥 Gestão de Acessos
            </h1>
            <div class="flex gap-4 items-center">
                <a href="index.php" class="text-sm text-slate-500 hover:text-emerald-600">Dashboard</a>
                <button @click="openModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg shadow transition transform hover:scale-105 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    <span>Adicionar Admin</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6">
        
        <div class="space-y-4">
            <template x-for="user in users" :key="user.id">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between transition hover:shadow-md group"
                     :class="{'border-l-4 border-l-emerald-500 bg-emerald-50/30': user.id == currentUserId}">
                    
                    <div class="flex items-center gap-4">
                        <img :src="'https://ui-avatars.com/api/?name=' + user.email + '&background=random&color=fff'" 
                             class="h-12 w-12 rounded-full border-2 border-white shadow-sm">
                        
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-800" x-text="user.email"></h3>
                                <template x-if="user.id == currentUserId">
                                    <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded-full font-bold">VOCÊ</span>
                                </template>
                            </div>
                            <p class="text-xs text-slate-500 font-mono">
                                ID: <span x-text="user.id"></span> • Criado em <span x-text="formatDate(user.created_at)"></span>
                            </p>
                        </div>
                    </div>

                    <div>
                        <template x-if="user.id != currentUserId">
                            <button @click="deleteUser(user.id, user.email)" class="text-slate-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition" title="Remover Acesso">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </template>
                        <template x-if="user.id == currentUserId">
                            <span class="text-xs text-slate-300 italic px-2">Logado</span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isModalOpen = false"></div>

            <div x-show="isModalOpen" x-transition.scale class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                
                <form @submit.prevent="createUser" class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">Novo Administrador</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">E-mail de Acesso</label>
                            <input type="email" x-model="form.email" required placeholder="nome@empresa.com" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        
                        <div x-data="{ show: false }">
                            <label class="block text-sm font-medium text-gray-700">Senha Provisória</label>
                            <div class="relative mt-1">
                                <input :type="show ? 'text' : 'password'" x-model="form.password" required class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500 pr-10">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.054 10.054 0 01-3.772 4.241m-9.03-9.03l9.03 9.03" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" :disabled="isLoading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none sm:col-start-2 sm:text-sm disabled:opacity-50">
                            <span x-show="!isLoading">Adicionar</span>
                            <span x-show="isLoading">Salvando...</span>
                        </button>
                        <button type="button" @click="isModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('usersManager', (initialUsers, currentId) => ({
                users: initialUsers,
                currentUserId: currentId,
                isModalOpen: false,
                isLoading: false,
                form: { email: '', password: '' },

                openModal() {
                    this.form = { email: '', password: '' };
                    this.isModalOpen = true;
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('pt-BR');
                },

                async createUser() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('users.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'create', ...this.form })
                        });
                        const data = await res.json();

                        if(data.success) {
                            window.location.reload();
                        } else {
                            alert('Erro: ' + data.message);
                        }
                    } catch(err) {
                        alert('Erro de conexão.');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async deleteUser(id, email) {
                    if(!confirm(`Tem certeza que deseja remover o acesso de ${email}?`)) return;

                    try {
                        const res = await fetch('users.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'delete', id: id })
                        });
                        const data = await res.json();

                        if(data.success) {
                            this.users = this.users.filter(u => u.id !== id);
                        } else {
                            alert(data.message);
                        }
                    } catch(err) {
                        alert('Erro ao excluir.');
                    }
                }
            }));
        });
    </script>
</body>
</html>