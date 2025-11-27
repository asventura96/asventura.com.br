<?php
// backend/admin/projects.php

// Configurações iniciais
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'auth.php';
require '../config.php';

// --- API HANDLING (POST REQUESTS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $action = $_POST['action'] ?? '';

        // 1. SALVAR (CRIAR OU EDITAR)
        if ($action === 'save') {
            $id = $_POST['id'] ?? null; // Se vier ID, é edição
            $title = $_POST['title'] ?? '';
            $desc = $_POST['description'] ?? '';
            $link = $_POST['link'] ?? '';
            $final_image_url = $_POST['existing_image'] ?? '';

            // Validação básica
            if (empty($title)) throw new Exception("O título é obrigatório.");

            // Upload de Imagem
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '.' . $ext;
                
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $filename)) {
                    $final_image_url = '/uploads/' . $filename;
                } else {
                    throw new Exception("Erro ao fazer upload da imagem.");
                }
            } elseif (!empty($_POST['image_url'])) {
                $final_image_url = $_POST['image_url'];
            }

            // Se for novo e não tiver imagem
            if (!$id && empty($final_image_url)) {
                // throw new Exception("Uma imagem é obrigatória para novos projetos."); 
                // Opcional: permitir sem imagem, ou descomentar a linha acima.
            }

            if ($id) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, link=?, image_url=? WHERE id=?");
                $stmt->execute([$title, $desc, $link, $final_image_url, $id]);
            } else {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO projects (title, description, link, image_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $desc, $link, $final_image_url]);
                $id = $pdo->lastInsertId();
            }

            // Retorna o objeto atualizado para atualizar a tela sem refresh
            $response['success'] = true;
            $response['message'] = "Projeto salvo com sucesso!";
            $response['project'] = [
                'id' => $id,
                'title' => $title,
                'description' => $desc,
                'link' => $link,
                'image_url' => $final_image_url
            ];
        }

        // 2. EXCLUIR
        if ($action === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = "Projeto excluído.";
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// --- VIEW NORMAL (GET) ---
// Busca todos os projetos para renderizar a carga inicial
$stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Projetos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans"
      x-data="projectsManager(<?php echo htmlspecialchars(json_encode($projects)); ?>)">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                📂 Meus Projetos
            </h1>
            <div class="flex gap-4 items-center">
                <a href="index.php" class="text-sm text-slate-500 hover:text-blue-600">Voltar ao Painel</a>
                <button @click="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition transform hover:scale-105 flex items-center gap-2">
                    <span>+ Novo Projeto</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <template x-if="projects.length === 0">
                <div class="col-span-full text-center py-20 bg-white rounded-xl border-2 border-dashed border-slate-300">
                    <p class="text-slate-400 text-lg">Nenhum projeto cadastrado ainda.</p>
                </div>
            </template>

            <template x-for="p in projects" :key="p.id">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition border border-slate-200 overflow-hidden flex flex-col h-full group">
                    <div class="h-48 bg-slate-100 relative overflow-hidden">
                        <img :src="getImageUrl(p.image_url)" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                            <button @click="openModal(p)" class="bg-white text-slate-800 p-2 rounded-full hover:bg-blue-50">
                                ✏️
                            </button>
                            <button @click="deleteProject(p.id)" class="bg-white text-red-600 p-2 rounded-full hover:bg-red-50">
                                🗑️
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-5 flex-grow flex flex-col">
                        <h3 class="font-bold text-lg text-slate-800 mb-1" x-text="p.title"></h3>
                        <p class="text-slate-500 text-sm line-clamp-3 mb-4 flex-grow" x-text="p.description"></p>
                        
                        <div x-show="p.link" class="mt-auto pt-4 border-t border-slate-100">
                            <a :href="p.link" target="_blank" class="text-xs text-blue-600 font-semibold hover:underline flex items-center gap-1">
                                🔗 Ver Link
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isModalOpen = false"></div>

            <div x-show="isModalOpen" x-transition.scale class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <form @submit.prevent="saveProject" class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" x-text="form.id ? 'Editar Projeto' : 'Novo Projeto'"></h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome do Projeto / Cliente</label>
                            <input type="text" x-model="form.title" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição</label>
                            <textarea x-model="form.description" rows="3" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Link do Projeto (Opcional)</label>
                            <input type="text" x-model="form.link" placeholder="https://..." class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="bg-slate-50 p-4 rounded border border-slate-200">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Imagem de Capa</label>
                            
                            <div x-show="form.existing_image" class="mb-3 flex items-center gap-3 bg-white p-2 rounded border">
                                <img :src="getImageUrl(form.existing_image)" class="h-10 w-10 object-cover rounded">
                                <span class="text-xs text-slate-500">Imagem atual mantida. Selecione outra abaixo para trocar.</span>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">Upload de Arquivo</span>
                                    <input type="file" x-ref="fileInput" accept="image/*" class="block w-full text-sm mt-1 text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                
                                <div class="text-center text-xs text-slate-400 font-bold">- OU -</div>

                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">URL Externa</span>
                                    <input type="text" x-model="form.image_url" placeholder="https://imgur.com/..." class="mt-1 w-full text-sm border border-gray-300 rounded px-2 py-1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" :disabled="isLoading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:col-start-2 sm:text-sm disabled:opacity-50">
                            <span x-show="!isLoading">Salvar Projeto</span>
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
            Alpine.data('projectsManager', (initialProjects) => ({
                projects: initialProjects,
                isModalOpen: false,
                isLoading: false,
                form: {
                    id: null, title: '', description: '', link: '', 
                    image_url: '', existing_image: ''
                },

                // Corrige URLs relativas vs absolutas para exibição
                getImageUrl(url) {
                    if (!url) return 'https://via.placeholder.com/300x200?text=Sem+Imagem';
                    if (url.startsWith('http')) return url;
                    // Ajuste para mostrar uploads locais (assume que o PHP roda na raiz ou /backend)
                    return url; 
                },

                openModal(project = null) {
                    // Reseta o input de arquivo manualmente
                    if(this.$refs.fileInput) this.$refs.fileInput.value = '';

                    if (project) {
                        this.form = { ...project, existing_image: project.image_url, image_url: '' }; // Clone
                    } else {
                        this.form = { id: null, title: '', description: '', link: '', image_url: '', existing_image: '' };
                    }
                    this.isModalOpen = true;
                },

                async saveProject() {
                    this.isLoading = true;

                    // Cria um FormData para enviar arquivo e texto juntos
                    const formData = new FormData();
                    formData.append('action', 'save');
                    if(this.form.id) formData.append('id', this.form.id);
                    formData.append('title', this.form.title);
                    formData.append('description', this.form.description);
                    formData.append('link', this.form.link);
                    formData.append('existing_image', this.form.existing_image);
                    
                    // Se digitou URL manual, manda ela.
                    if(this.form.image_url) formData.append('image_url', this.form.image_url);
                    
                    // Se selecionou arquivo, manda o arquivo.
                    const file = this.$refs.fileInput.files[0];
                    if(file) formData.append('image_file', file);

                    try {
                        const res = await fetch('projects.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();

                        if (data.success) {
                            if (this.form.id) {
                                // Update Local
                                const index = this.projects.findIndex(p => p.id == this.form.id);
                                if(index !== -1) this.projects[index] = data.project;
                            } else {
                                // Insert Local
                                this.projects.unshift(data.project);
                            }
                            this.isModalOpen = false;
                        } else {
                            alert('Erro: ' + data.message);
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Erro ao conectar com o servidor.');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async deleteProject(id) {
                    if(!confirm('Tem certeza que deseja excluir este projeto?')) return;

                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    try {
                        const res = await fetch('projects.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();

                        if(data.success) {
                            this.projects = this.projects.filter(p => p.id !== id);
                        } else {
                            alert('Erro: ' + data.message);
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