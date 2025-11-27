<?php
// backend/admin/forms.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'auth.php';
require '../config.php';

// --- API HANDLING (JSON POST) ---
// Processa as requisições AJAX do Alpine.js
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $action = $input['action'] ?? '';

        // 1. SALVAR (CRIAR OU ATUALIZAR)
        if ($action === 'save') {
            $id = $input['id'] ?? null;
            $title = $input['title'];
            $slug = $input['slug'];
            $email = $input['recipient_email'];

            if (empty($title) || empty($slug) || empty($email)) {
                throw new Exception("Todos os campos são obrigatórios.");
            }

            if ($id) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE forms SET title=?, slug=?, recipient_email=? WHERE id=?");
                $stmt->execute([$title, $slug, $email, $id]);
            } else {
                // INSERT
                // Verifica duplicidade de slug
                $check = $pdo->prepare("SELECT id FROM forms WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->rowCount() > 0) throw new Exception("Este Slug já está em uso.");

                $stmt = $pdo->prepare("INSERT INTO forms (title, slug, recipient_email) VALUES (?, ?, ?)");
                $stmt->execute([$title, $slug, $email]);
                $id = $pdo->lastInsertId();
            }

            $response['success'] = true;
            $response['message'] = "Formulário salvo!";
        }

        // 2. EXCLUIR
        if ($action === 'delete') {
            $id = $input['id'];
            // O DELETE CASCADE no banco já apaga os campos e submissões
            $stmt = $pdo->prepare("DELETE FROM forms WHERE id = ?");
            $stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = "Formulário excluído.";
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// --- VIEW NORMAL (GET) ---
$forms = $pdo->query("SELECT * FROM forms ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Formulários</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans"
      x-data="formsManager(<?php echo htmlspecialchars(json_encode($forms)); ?>)">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                📝 Gerenciador de Formulários
            </h1>
            <div class="flex gap-4 items-center">
                <a href="index.php" class="text-sm text-slate-500 hover:text-blue-600">Dashboard</a>
                <button @click="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition transform hover:scale-105 flex items-center gap-2">
                    <span>+ Novo Formulário</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <template x-if="forms.length === 0">
                <div class="col-span-full text-center py-20 bg-white rounded-xl border-2 border-dashed border-slate-300">
                    <p class="text-slate-400 text-lg mb-2">Você ainda não criou nenhum formulário.</p>
                    <button @click="openModal()" class="text-blue-600 font-bold hover:underline">Criar o primeiro</button>
                </div>
            </template>

            <template x-for="form in forms" :key="form.id">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition border border-slate-200 p-6 flex flex-col relative group">
                    
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-slate-800" x-text="form.title"></h3>
                            <div class="text-xs font-mono text-slate-400 bg-slate-100 px-2 py-1 rounded mt-1 inline-block" x-text="form.slug"></div>
                        </div>
                        
                        <div class="flex gap-1">
                            <button @click="openModal(form)" class="text-slate-400 hover:text-blue-600 p-1" title="Editar Configurações">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </button>
                            <button @click="deleteForm(form.id)" class="text-slate-400 hover:text-red-600 p-1" title="Excluir Formulário">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mb-6 text-sm text-slate-500">
                        <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Envia para:</span>
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            <span x-text="form.recipient_email"></span>
                        </div>
                    </div>

                    <div class="mt-auto grid grid-cols-2 gap-3">
                        <a :href="'fields.php?form_id=' + form.id" class="flex items-center justify-center gap-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 py-2 rounded-lg font-bold text-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Editar Campos
                        </a>
                        <a :href="'submissions.php?form_id=' + form.id" class="flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 py-2 rounded-lg font-bold text-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            Ver Envios
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isModalOpen = false"></div>

            <div x-show="isModalOpen" x-transition.scale class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <form @submit.prevent="saveForm" class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" x-text="currentForm.id ? 'Configurar Formulário' : 'Novo Formulário'"></h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome do Formulário</label>
                            <input type="text" x-model="currentForm.title" placeholder="Ex: Contato Principal" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug (ID para o Código)</label>
                            <input type="text" x-model="currentForm.slug" placeholder="Ex: contato-site" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 font-mono text-sm focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-400 mt-1">Use apenas letras, números e hífens. Sem espaços.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Receber e-mails em:</label>
                            <input type="email" x-model="currentForm.recipient_email" placeholder="admin@empresa.com" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" :disabled="isLoading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:col-start-2 sm:text-sm disabled:opacity-50">
                            <span x-show="!isLoading">Salvar</span>
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
            Alpine.data('formsManager', (initialForms) => ({
                forms: initialForms,
                isModalOpen: false,
                isLoading: false,
                currentForm: { id: null, title: '', slug: '', recipient_email: '' },

                openModal(form = null) {
                    if (form) {
                        this.currentForm = { ...form };
                    } else {
                        this.currentForm = { id: null, title: '', slug: '', recipient_email: '' };
                    }
                    this.isModalOpen = true;
                },

                async saveForm() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('forms.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'save',
                                ...this.currentForm
                            })
                        });
                        const data = await res.json();

                        if (data.success) {
                            window.location.reload(); // Recarrega para simplificar atualização da lista
                        } else {
                            alert('Erro: ' + data.message);
                        }
                    } catch (err) {
                        alert('Erro ao conectar.');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async deleteForm(id) {
                    if(!confirm('ATENÇÃO: Isso apagará TODOS os campos e submissões (respostas) deste formulário. Deseja continuar?')) return;

                    try {
                        const res = await fetch('forms.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'delete', id: id })
                        });
                        const data = await res.json();
                        
                        if(data.success) {
                            this.forms = this.forms.filter(f => f.id !== id);
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