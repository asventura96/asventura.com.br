<?php
// backend/admin/fields.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'auth.php';
require '../config.php';

header('Content-Type: text/html; charset=utf-8');

$form_id = $_GET['form_id'] ?? null;
if (!$form_id) die("Form ID ausente.");

// --- API HANDLING (AJAX) ---
// Se receber um POST JSON, processa aqui e encerra
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');

    // 1. Salvar ou Atualizar Campo
    if (isset($input['action']) && $input['action'] === 'save_field') {
        try {
            $label = $input['label'];
            $name = $input['name'] ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $label)));
            $type = $input['type'];
            // Converte array de opções para JSON para salvar no banco
            $options = isset($input['options']) ? json_encode($input['options']) : null; 
            $required = $input['required'] ? 1 : 0;
            $placeholder = $input['placeholder'] ?? '';
            $id = $input['id'] ?? null;

            if ($id) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE form_fields SET label=?, name=?, type=?, options=?, is_required=?, placeholder=? WHERE id=? AND form_id=?");
                $stmt->execute([$label, $name, $type, $options, $required, $placeholder, $id, $form_id]);
            } else {
                // INSERT
                // Pega o maior order_index para colocar no final
                $stmtOrder = $pdo->prepare("SELECT MAX(order_index) as max_ord FROM form_fields WHERE form_id = ?");
                $stmtOrder->execute([$form_id]);
                $nextOrder = ($stmtOrder->fetch()['max_ord'] ?? 0) + 1;

                $stmt = $pdo->prepare("INSERT INTO form_fields (form_id, label, name, type, options, is_required, placeholder, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$form_id, $label, $name, $type, $options, $required, $placeholder, $nextOrder]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // 2. Reordenar Campos
    if (isset($input['action']) && $input['action'] === 'reorder') {
        $order = $input['order']; // Array de IDs na nova ordem
        foreach ($order as $index => $fieldId) {
            $stmt = $pdo->prepare("UPDATE form_fields SET order_index = ? WHERE id = ? AND form_id = ?");
            $stmt->execute([$index, $fieldId, $form_id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // 3. Deletar Campo
    if (isset($input['action']) && $input['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM form_fields WHERE id = ? AND form_id = ?");
        $stmt->execute([$input['id'], $form_id]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// --- VIEW NORMAL ---
// Busca dados do form
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

// Busca campos ordenados
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY order_index ASC");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepara campos para o JS (converte options JSON de volta para array)
foreach ($fields as &$f) {
    $f['options'] = json_decode($f['options'] ?? '[]');
    // Fallback para legado (se era separado por vírgula)
    if (json_last_error() !== JSON_ERROR_NONE && !empty($f['options'])) {
        $f['options'] = explode(',', $f['options']); // converte legado para array
    }
}
unset($f);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Construtor de Campos - <?php echo htmlspecialchars($form['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        .ghost-class { opacity: 0.5; background: #f3f4f6; border: 2px dashed #cbd5e1; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen font-sans"
      x-data="formBuilder(<?php echo htmlspecialchars(json_encode($fields)); ?>, <?php echo $form_id; ?>)">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="forms.php" class="text-slate-500 hover:text-blue-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    <?php echo htmlspecialchars($form['title']); ?>
                </h1>
            </div>
            <button @click="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Novo Campo
            </button>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto p-6 mt-4">
        
        <div id="fields-list" class="space-y-3">
            <template x-if="fields.length === 0">
                <div class="text-center py-16 bg-white rounded-xl border-2 border-dashed border-slate-300">
                    <p class="text-slate-400 text-lg">Nenhum campo criado ainda.</p>
                    <button @click="openModal()" class="text-blue-600 font-semibold mt-2 hover:underline">Adicionar o primeiro</button>
                </div>
            </template>

            <template x-for="(field, index) in fields" :key="field.id">
                <div :data-id="field.id" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition flex items-center justify-between group">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="drag-handle text-slate-300 hover:text-slate-500 p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-lg text-slate-800" x-text="field.label"></span>
                                <span x-show="field.is_required" class="text-red-500 text-xs font-bold bg-red-50 px-2 py-0.5 rounded-full">Obrigatório</span>
                            </div>
                            <div class="text-xs text-slate-500 flex gap-2">
                                <span class="uppercase font-semibold tracking-wider bg-slate-100 px-1.5 rounded" x-text="getTypeLabel(field.type)"></span>
                                <span x-text="field.name"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openModal(field)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </button>
                        <button @click="deleteField(field.id)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Excluir">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="isModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title" x-text="currentField.id ? 'Editar Campo' : 'Novo Campo'"></h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rótulo (Label)</label>
                            <input type="text" x-model="currentField.label" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de Dado</label>
                            <select x-model="currentField.type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="text">Texto Curto</option>
                                <option value="email">E-mail</option>
                                <option value="tel">Telefone/WhatsApp</option>
                                <option value="number">Número</option>
                                <option value="date">Data</option>
                                <option value="textarea">Texto Longo (Mensagem)</option>
                                <option disabled>--- Seleção ---</option>
                                <option value="select">Lista (Select)</option>
                                <option value="radio">Múltipla Escolha (Radio)</option>
                                <option value="checkbox">Caixa de Seleção (Checkbox)</option>
                                <option disabled>--- Avançado ---</option>
                                <option value="file">Arquivo/Anexo</option>
                            </select>
                        </div>

                        <div x-show="['text','email','tel','number','textarea'].includes(currentField.type)">
                            <label class="block text-sm font-medium text-gray-700">Texto de Ajuda (Placeholder)</label>
                            <input type="text" x-model="currentField.placeholder" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div x-show="['select','radio','checkbox'].includes(currentField.type)" class="bg-slate-50 p-3 rounded-md border border-slate-200">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Opções da Lista</label>
                            
                            <ul class="space-y-2 mb-2">
                                <template x-for="(opt, idx) in currentField.options" :key="idx">
                                    <li class="flex gap-2">
                                        <input type="text" x-model="currentField.options[idx]" class="flex-1 border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 outline-none">
                                        <button @click="removeOption(idx)" class="text-red-500 hover:bg-red-100 px-2 rounded">×</button>
                                    </li>
                                </template>
                            </ul>
                            <button @click="addOption()" class="text-sm text-blue-600 font-medium hover:underline flex items-center gap-1">
                                + Adicionar Opção
                            </button>
                        </div>

                        <div class="flex items-center">
                            <input id="req" type="checkbox" x-model="currentField.required" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="req" class="ml-2 block text-sm text-gray-900">Preenchimento Obrigatório</label>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="saveField()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Salvar Campo
                    </button>
                    <button @click="isModalOpen = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formBuilder', (initialFields, formId) => ({
                fields: initialFields,
                formId: formId,
                isModalOpen: false,
                currentField: { id: null, label: '', type: 'text', options: [], required: false, placeholder: '', name: '' },

                init() {
                    // Inicializa Drag and Drop
                    let el = document.getElementById('fields-list');
                    Sortable.create(el, {
                        animation: 150,
                        handle: '.drag-handle',
                        ghostClass: 'ghost-class',
                        onEnd: (evt) => {
                            this.reorderFields();
                        }
                    });
                },

                getTypeLabel(type) {
                    const map = {
                        'text': 'Texto', 'email': 'Email', 'textarea': 'Texto Longo', 
                        'select': 'Lista', 'radio': 'Seleção Única', 'checkbox': 'Seleção Múltiple',
                        'tel': 'Telefone', 'number': 'Número', 'file': 'Arquivo', 'date': 'Data'
                    };
                    return map[type] || type;
                },

                openModal(field = null) {
                    if (field) {
                        // Modo Edição (Clona o objeto para não editar reativo direto na lista antes de salvar)
                        this.currentField = JSON.parse(JSON.stringify(field));
                        // Garante que options é array
                        if(!Array.isArray(this.currentField.options)) this.currentField.options = [];
                        this.currentField.required = !!field.is_required; // cast int to bool
                    } else {
                        // Modo Novo
                        this.currentField = { id: null, label: '', type: 'text', options: [], required: true, placeholder: '', name: '' };
                    }
                    this.isModalOpen = true;
                },

                addOption() {
                    this.currentField.options.push('Nova Opção');
                },

                removeOption(index) {
                    this.currentField.options.splice(index, 1);
                },

                async saveField() {
                    if(!this.currentField.label) return alert('O nome do campo é obrigatório');

                    const payload = {
                        action: 'save_field',
                        ...this.currentField
                    };

                    await this.sendRequest(payload);
                    window.location.reload(); // Reload simples para atualizar tudo limpo
                },

                async deleteField(id) {
                    if(!confirm('Tem certeza? Isso apagará os dados preenchidos deste campo em submissões antigas.')) return;
                    await this.sendRequest({ action: 'delete', id: id });
                    this.fields = this.fields.filter(f => f.id !== id);
                },

                async reorderFields() {
                    // Pega a nova ordem baseada no DOM
                    const order = [];
                    document.querySelectorAll('#fields-list > div').forEach(el => {
                        order.push(el.getAttribute('data-id'));
                    });
                    
                    await this.sendRequest({ action: 'reorder', order: order });
                },

                async sendRequest(data) {
                    const response = await fetch(`fields.php?form_id=${this.formId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    return await response.json();
                }
            }));
        });
    </script>
</body>
</html>