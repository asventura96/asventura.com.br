<?php
// backend/admin/settings.php

require 'auth.php';
require '../config.php'; 
require 'includes/Layout.php';
require 'includes/Components.php';

// --- API HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if (!isset($_POST['action']) || $_POST['action'] !== 'save') throw new Exception("Ação inválida.");
        
        // CAMPOS QUE SERÃO SALVOS (SMTP REMOVIDO)
        $fields = [
            'site_title', 'site_description', 
            'contact_email', 'contact_phone', 'contact_address',
            'social_links' // JSON das redes sociais
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $val = $_POST[$field];
                // Se for social_links e vier vazio, salva array vazio
                if ($field === 'social_links' && empty($val)) $val = '[]';
                
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                    ->execute([$field, $val, $val]);
            }
        }
        
        $response = ['success' => true, 'message' => 'Configurações salvas!'];
        
        // Uploads (Mantidos)
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        foreach (['site_logo', 'site_favicon'] as $f) {
            if (isset($_FILES[$f]) && $_FILES[$f]['error'] === 0) {
                $ext = pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION);
                $newName = $f . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$f]['tmp_name'], $uploadDir . $newName)) {
                    $path = '/uploads/' . $newName;
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                        ->execute([$f, $path, $path]);
                    $response[$f] = $path;
                }
            }
        }

        echo json_encode($response);
    } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
    exit;
}

// --- VIEW ---
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $settings[$row['setting_key']] = $row['setting_value'];

// Garante chaves padrão
$keys = ['site_title', 'site_description', 'site_logo', 'site_favicon', 'contact_email', 'contact_phone', 'contact_address', 'social_links'];
foreach($keys as $k) { if(!isset($settings[$k])) $settings[$k] = ''; }

// Garante que social_links seja um JSON válido
if (empty($settings['social_links'])) $settings['social_links'] = '[]';

Layout::header('Configurações');
?>

<div x-data="settingsManager(<?php echo htmlspecialchars(json_encode($settings)); ?>)" class="pb-20">
    
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-brand-dark dark:text-white">Configurações Gerais</h1>
        <p class="text-slate-500 dark:text-slate-400">Gerencie a identidade e contatos do sistema.</p>
    </div>

    <form @submit.prevent="saveSettings" class="space-y-8 max-w-6xl">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-8">
                
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="bg-purple-50 dark:bg-slate-700/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                        <span class="text-brand-purple text-xl">🎨</span>
                        <h2 class="font-bold text-brand-dark dark:text-white">Identidade</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <?php echo UI::input('Título do Site', 'data.site_title', 'text'); ?>
                        <?php echo UI::input('Descrição (SEO)', 'data.site_description', 'textarea'); ?>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Logotipo</label>
                                <div class="relative group cursor-pointer border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg h-32 flex flex-col items-center justify-center hover:border-brand-purple transition bg-slate-50 dark:bg-slate-900">
                                    <template x-if="previews.logo">
                                        <img :src="previews.logo" class="h-full w-full object-contain p-2">
                                    </template>
                                    <template x-if="!previews.logo">
                                        <span class="text-slate-400 text-sm">Upload Logo</span>
                                    </template>
                                    <input type="file" @change="handleFile($event, 'site_logo', 'logo')" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Favicon</label>
                                <div class="relative group cursor-pointer border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg h-32 flex flex-col items-center justify-center hover:border-brand-purple transition bg-slate-50 dark:bg-slate-900">
                                    <template x-if="previews.favicon">
                                        <img :src="previews.favicon" class="h-12 w-12 object-contain">
                                    </template>
                                    <template x-if="!previews.favicon">
                                        <span class="text-slate-400 text-sm">Upload Ícone</span>
                                    </template>
                                    <input type="file" @change="handleFile($event, 'site_favicon', 'favicon')" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="bg-green-50 dark:bg-slate-700/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                        <span class="text-green-600 text-xl">📞</span>
                        <h2 class="font-bold text-brand-dark dark:text-white">Fale Conosco</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <?php echo UI::input('E-mail de Contato', 'data.contact_email', 'email'); ?>
                        <?php echo UI::input('Telefone / WhatsApp', 'data.contact_phone', 'text'); ?>
                        <?php echo UI::input('Endereço Completo', 'data.contact_address', 'text'); ?>
                    </div>
                </div>

            </div>

            <div class="space-y-8">

                 <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="bg-pink-50 dark:bg-slate-700/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                        <span class="text-pink-600 text-xl">🌎</span>
                        <h2 class="font-bold text-brand-dark dark:text-white">Redes Sociais</h2>
                    </div>
                    
                    <div class="p-6 bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Adicionar Nova Rede</label>
                        <div class="flex gap-2">
                            <div class="relative w-1/3">
                                <select x-model="newSocial.platform" class="w-full h-11 pl-9 pr-4 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg appearance-none focus:ring-2 focus:ring-brand-blue outline-none text-sm font-bold text-slate-700 dark:text-white">
                                    <template x-for="(p, key) in platforms" :key="key">
                                        <option :value="key" x-text="p.name"></option>
                                    </template>
                                </select>
                                <div class="absolute left-3 top-3 pointer-events-none" x-html="platforms[newSocial.platform].icon"></div>
                            </div>
                            
                            <input type="text" x-model="newSocial.url" :placeholder="platforms[newSocial.platform].placeholder" 
                                   @keydown.enter.prevent="addSocial()"
                                   class="flex-1 h-11 px-4 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-brand-blue outline-none text-sm text-slate-700 dark:text-white">
                            
                            <button type="button" @click="addSocial()" class="h-11 w-11 flex items-center justify-center bg-brand-dark hover:bg-black text-white rounded-lg transition shadow-lg shadow-brand-dark/20">
                                <span class="text-xl font-bold">+</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-2">
                        <template x-if="socials.length === 0">
                            <div class="text-center py-6 text-slate-400 text-sm">Nenhuma rede social adicionada.</div>
                        </template>
                        <ul class="space-y-2">
                            <template x-for="(item, index) in socials" :key="index">
                                <li class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-lg group hover:border-brand-blue transition">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-html="platforms[item.platform]?.icon || '🔗'"></div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-sm text-brand-dark dark:text-white" x-text="platforms[item.platform]?.name || item.platform"></div>
                                        <div class="text-xs text-slate-400 truncate" x-text="item.url"></div>
                                    </div>

                                    <button type="button" @click="removeSocial(index)" class="p-2 text-slate-300 hover:text-red-500 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                </div>
        </div>

        <div class="fixed bottom-6 right-6 z-30">
            <button type="submit" :disabled="isLoading" class="flex items-center gap-2 bg-brand-green hover:bg-green-600 text-white font-bold py-3 px-6 rounded-full shadow-xl hover:shadow-2xl transition transform hover:-translate-y-1 disabled:opacity-70">
                <span x-show="!isLoading">💾 Salvar Alterações</span>
                <span x-show="isLoading">Salvando...</span>
            </button>
        </div>

    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        // Ícones SVG para as plataformas
        const icons = {
            linkedin: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>',
            github: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>',
            instagram: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
            whatsapp: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.992 4.304 4.735-1.637z"/></svg>',
            youtube: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>',
            default: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'
        };

        Alpine.data('settingsManager', (initData) => ({
            data: initData,
            socials: [], // Array dedicado para redes
            newSocial: { platform: 'instagram', url: '' },
            platforms: {
                instagram: { name: 'Instagram', icon: icons.instagram, placeholder: 'https://instagram.com/usuario' },
                linkedin: { name: 'LinkedIn', icon: icons.linkedin, placeholder: 'https://linkedin.com/in/usuario' },
                github: { name: 'GitHub', icon: icons.github, placeholder: 'https://github.com/usuario' },
                whatsapp: { name: 'WhatsApp', icon: icons.whatsapp, placeholder: 'https://wa.me/55...' },
                youtube: { name: 'YouTube', icon: icons.youtube, placeholder: 'https://youtube.com/...' },
                site: { name: 'Outro Site', icon: icons.default, placeholder: 'https://...' }
            },
            isLoading: false,
            files: { site_logo: null, site_favicon: null },
            previews: { logo: initData.site_logo, favicon: initData.site_favicon },

            init() {
                // Parse do JSON vindo do PHP
                try {
                    this.socials = typeof this.data.social_links === 'string' 
                        ? JSON.parse(this.data.social_links) 
                        : this.data.social_links;
                    if(!Array.isArray(this.socials)) this.socials = [];
                } catch(e) { this.socials = []; }
            },

            addSocial() {
                if(!this.newSocial.url) return alert('Cole a URL primeiro!');
                this.socials.push({ ...this.newSocial });
                this.newSocial.url = ''; // Limpa input
            },

            removeSocial(index) {
                this.socials.splice(index, 1);
            },

            handleFile(e, field, prevKey) {
                const file = e.target.files[0];
                if(file) {
                    this.files[field] = file;
                    this.previews[prevKey] = URL.createObjectURL(file);
                }
            },

            async saveSettings() {
                this.isLoading = true;
                const formData = new FormData();
                formData.append('action', 'save');
                
                // Atualiza o JSON de sociais antes de salvar
                this.data.social_links = JSON.stringify(this.socials);

                for(let k in this.data) formData.append(k, this.data[k]);
                if(this.files.site_logo) formData.append('site_logo', this.files.site_logo);
                if(this.files.site_favicon) formData.append('site_favicon', this.files.site_favicon);

                try {
                    const res = await fetch('settings.php', { method: 'POST', body: formData });
                    const r = await res.json();
                    if(r.success) { 
                         alert('Salvo com sucesso!'); 
                         if(r.site_logo) this.data.site_logo = r.site_logo;
                         if(r.site_favicon) this.data.site_favicon = r.site_favicon;
                    } else alert('Erro: ' + r.message);
                } catch(e) { alert('Erro de conexão'); console.error(e); }
                finally { this.isLoading = false; }
            }
        }));
    });
</script>
<?php Layout::footer(); ?>