<?php
// backend/admin/submissions.php

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

        // 1. EXCLUIR SUBMISSÃO
        if ($action === 'delete') {
            $id = $input['id'];
            $stmt = $pdo->prepare("DELETE FROM form_submissions WHERE id = ?");
            $stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = "Registro excluído.";
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// --- VIEW (GET) ---
$form_id = $_GET['form_id'] ?? null;
if (!$form_id) { header("Location: forms.php"); exit; }

// Busca Info do Formulário
$stmt = $pdo->prepare("SELECT title FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) die("Formulário não encontrado.");

// Busca Submissões
$stmtSub = $pdo->prepare("SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC");
$stmtSub->execute([$form_id]);
$rawSubmissions = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

// Prepara dados para o Frontend (Decodifica o JSON 'data' para objeto real)
$submissions = [];
foreach ($rawSubmissions as $sub) {
    $sub['parsed_data'] = json_decode($sub['data'], true);
    // Cria um resumo para a tabela (primeiros 3 campos)
    $sub['summary'] = array_slice($sub['parsed_data'], 0, 3); 
    $submissions[] = $sub;
}

// Descobre as chaves (colunas) baseadas no último envio (para o cabeçalho da tabela)
$columns = [];
if (!empty($submissions)) {
    $columns = array_keys($submissions[0]['parsed_data']);
    // Pega só as 4 primeiras colunas para não quebrar a tabela visualmente
    $columns = array_slice($columns, 0, 4); 
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório - <?php echo htmlspecialchars($form['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans"
      x-data="submissionsManager(<?php echo htmlspecialchars(json_encode($submissions)); ?>, <?php echo htmlspecialchars(json_encode($columns)); ?>)">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Relatório de Envios</div>
                <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <?php echo htmlspecialchars($form['title']); ?>
                    <span class="bg-slate-100 text-slate-500 text-xs px-2 py-0.5 rounded-full" x-text="items.length + ' registros'"></span>
                </h1>
            </div>
            <div class="flex gap-3">
                <a href="forms.php" class="text-sm text-slate-500 hover:text-blue-600 flex items-center gap-1 px-3 py-2">
                    ← Voltar
                </a>
                
                <button @click="exportCSV()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 text-sm font-bold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Baixar Excel (CSV)
                </button>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6">
        
        <template x-if="items.length === 0">
            <div class="text-center py-20 bg-white rounded-xl border-2 border-dashed border-slate-300">
                <div class="bg-slate-50 p-4 rounded-full inline-block mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-slate-500 text-lg">Nenhuma mensagem recebida ainda.</p>
            </div>
        </template>

        <template x-if="items.length > 0">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 font-bold w-10">#</th>
                                <th class="px-6 py-4 font-bold">Data</th>
                                <th class="px-6 py-4 font-bold">Status</th>
                                <template x-for="col in columns" :key="col">
                                    <th class="px-6 py-4 font-bold capitalize" x-text="col.replace(/_/g, ' ')"></th>
                                </template>
                                <th class="px-6 py-4 font-bold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr class="hover:bg-slate-50 transition cursor-pointer" @click="openModal(item)">
                                    <td class="px-6 py-4 text-slate-400 font-mono text-xs" x-text="item.id"></td>
                                    <td class="px-6 py-4 text-slate-600 font-mono" x-text="formatDate(item.created_at)"></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold"
                                              :class="item.email_status === 'Enviado' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                              x-text="item.email_status">
                                        </span>
                                    </td>
                                    <template x-for="col in columns" :key="col">
                                        <td class="px-6 py-4 text-slate-700 max-w-[200px] truncate" x-text="item.parsed_data[col] || '-'"></td>
                                    </template>
                                    
                                    <td class="px-6 py-4 text-right">
                                        <button @click.stop="openModal(item)" class="text-blue-600 hover:text-blue-800 font-bold text-xs mr-3">Ver Detalhes</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>

    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="isModalOpen = false"></div>

            <div x-show="isModalOpen" x-transition.scale class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                
                <div class="bg-slate-800 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-bold text-white">Detalhes do Envio</h3>
                        <p class="text-slate-400 text-xs font-mono mt-1" x-text="'ID: #' + currentItem.id + ' • ' + formatDate(currentItem.created_at)"></p>
                    </div>
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto bg-white">
                    <div class="grid grid-cols-1 gap-4">
                        <template x-for="(value, key) in currentItem.parsed_data" :key="key">
                            <div class="border-b border-slate-100 pb-3 last:border-0">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1" x-text="key.replace(/_/g, ' ')"></label>
                                <div class="text-slate-800 text-base leading-relaxed whitespace-pre-line" x-text="value"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse justify-between items-center border-t border-slate-200">
                    <button @click="isModalOpen = false" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm">
                        Fechar
                    </button>
                    
                    <button @click="deleteItem(currentItem.id)" type="button" class="mt-3 sm:mt-0 text-red-600 hover:text-red-800 text-sm font-bold flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Excluir este registro
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('submissionsManager', (initialItems, initialColumns) => ({
                items: initialItems,
                columns: initialColumns,
                isModalOpen: false,
                currentItem: { id: null, created_at: '', parsed_data: {} },

                openModal(item) {
                    this.currentItem = item;
                    this.isModalOpen = true;
                },

                formatDate(dateString) {
                    if(!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleString('pt-BR');
                },

                async deleteItem(id) {
                    if(!confirm('Tem certeza que deseja excluir permanentemente este registro?')) return;

                    try {
                        const res = await fetch('submissions.php?form_id=<?php echo $form_id; ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'delete', id: id })
                        });
                        const data = await res.json();
                        
                        if(data.success) {
                            this.items = this.items.filter(i => i.id !== id);
                            this.isModalOpen = false;
                        } else {
                            alert(data.message);
                        }
                    } catch(e) {
                        alert('Erro ao excluir.');
                    }
                },

                exportCSV() {
                    if(this.items.length === 0) return alert('Nada para exportar.');

                    // Pega todas as chaves possíveis de todos os itens (caso algum tenha campos a mais)
                    let allKeys = new Set();
                    this.items.forEach(item => {
                        Object.keys(item.parsed_data).forEach(k => allKeys.add(k));
                    });
                    const headers = Array.from(allKeys);
                    
                    // Monta o CSV
                    let csvContent = "data:text/csv;charset=utf-8,";
                    
                    // Cabeçalho: ID, Data, Status, ...Campos
                    csvContent += "ID,Data,Status," + headers.join(",") + "\r\n";

                    this.items.forEach(item => {
                        let row = [
                            item.id,
                            this.formatDate(item.created_at).replace(',', ''), // Remove virgula da data pra não quebrar CSV
                            item.email_status
                        ];

                        headers.forEach(key => {
                            let val = item.parsed_data[key] || '';
                            // Trata quebras de linha e aspas para CSV
                            val = val.toString().replace(/"/g, '""'); 
                            if (val.search(/("|,|\n)/g) >= 0) val = `"${val}"`;
                            row.push(val);
                        });

                        csvContent += row.join(",") + "\r\n";
                    });

                    // Download
                    const encodedUri = encodeURI(csvContent);
                    const link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "relatorio_form_<?php echo $form_id; ?>.csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }));
        });
    </script>
</body>
</html>