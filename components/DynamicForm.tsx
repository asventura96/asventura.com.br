// components/DynamicForm.tsx

'use client';

import { useState, useEffect } from 'react';
// Se o arquivo api.ts não existir, troque API_BASE_URL por "http://localhost/backend" (ou sua URL) diretamente
import { API_BASE_URL } from '../utils/api'; 

interface Field {
  id: number;
  label: string;
  name: string;
  type: string; 
  options: string | string[] | null; // Aceita Array (novo) ou String (velho)
  placeholder?: string; // Adicionado
  is_required: number | boolean; // O PHP pode mandar 0/1 ou true/false
}

interface FormStructure {
  id: number;
  title: string;
}

export default function DynamicForm({ slug }: { slug: string }) {
  const [fields, setFields] = useState<Field[]>([]);
  const [formInfo, setFormInfo] = useState<FormStructure | null>(null);
  const [formData, setFormData] = useState<Record<string, string>>({});
  
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');

  // --- 1. LÓGICA INTELIGENTE DE OPÇÕES (Corrige o bug do JSON) ---
  const renderOptions = (options: string | string[] | null) => {
    if (!options) return null;
    
    let opts: string[] = [];

    if (Array.isArray(options)) {
      opts = options; // Já é array limpo do PHP novo
    } else {
      // Tenta decodificar caso venha string JSON ou separado por vírgula (Legado)
      try {
        if (options.startsWith('[')) {
            opts = JSON.parse(options);
        } else {
            opts = options.split(',');
        }
      } catch {
        opts = options.split(',');
      }
    }

    return opts.map((opt, index) => {
      const val = typeof opt === 'string' ? opt.trim() : opt;
      return (
        <option key={index} value={val}>
          {val}
        </option>
      );
    });
  };

  // --- 2. BUSCAR ESTRUTURA ---
  useEffect(() => {
    async function loadForm() {
      try {
        // Fallback caso API_BASE_URL não esteja definida
        const baseUrl = typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : 'http://localhost/backend';
        const res = await fetch(`${baseUrl}/api_form.php?slug=${slug}`);
        const json = await res.json();
        
        if (json.success !== undefined && !json.success) {
            console.error("Erro API:", json);
            return;
        }

        if (json.fields) {
          setFields(json.fields);
          setFormInfo(json.form);
        }
      } catch (e) {
        console.error("Falha ao carregar form:", e);
      } finally {
        setLoading(false);
      }
    }
    if(slug) loadForm();
  }, [slug]);

  // --- 3. GERENCIAR DIGITAÇÃO ---
  const handleChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  // --- 4. ENVIAR DADOS (SUBMIT) ---
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setStatus('sending');

    if (!formInfo) return;

    try {
      const baseUrl = typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : 'http://localhost/backend';
      
      const res = await fetch(`${baseUrl}/api_submit.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          form_id: formInfo.id,
          data: formData
        })
      });

      const result = await res.json();

      if (result.success) {
        setStatus('success');
        setFormData({}); // Limpa os campos visualmente
        // Opcional: Resetar o form HTML
        (e.target as HTMLFormElement).reset();
      } else {
        setStatus('error');
      }
    } catch (error) {
      console.error(error);
      setStatus('error');
    }
  };

  if (loading) return <div className="text-brand-blue animate-pulse font-rajdhani">Carregando formulário...</div>;
  if (fields.length === 0) return <div className="text-gray-500 font-rajdhani">Formulário indisponível.</div>;

  return (
    <form onSubmit={handleSubmit} className="space-y-6 bg-brand-dark p-6 sm:p-8 rounded-xl border border-white/5 shadow-2xl relative">
      
      {/* Campos Dinâmicos */}
      <div className="grid grid-cols-1 gap-6">
        {fields.map((field) => {
           // Normaliza booleano (PHP as vezes manda 0 ou 1)
           const isReq = field.is_required === 1 || field.is_required === true;
           
           return (
            <div key={field.id}>
                <label className="block text-sm font-bold text-brand-blue mb-2 uppercase font-rajdhani tracking-wider">
                {field.label} {isReq && <span className="text-brand-orange">*</span>}
                </label>

                {field.type === 'textarea' ? (
                <textarea
                    required={isReq}
                    rows={4}
                    placeholder={field.placeholder || ''}
                    className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition-all placeholder:text-white/20"
                    onChange={(e) => handleChange(field.name, e.target.value)}
                    value={formData[field.name] || ''}
                />
                ) : field.type === 'select' ? (
                <div className="relative">
                    <select
                        required={isReq}
                        className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 appearance-none focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition-all cursor-pointer"
                        onChange={(e) => handleChange(field.name, e.target.value)}
                        value={formData[field.name] || ''}
                    >
                        <option value="">Selecione...</option>
                        {renderOptions(field.options)}
                    </select>
                    {/* Seta Customizada */}
                    <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-brand-green">
                        <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
                ) : (
                <input
                    type={field.type} // text, email, tel, etc
                    required={isReq}
                    placeholder={field.placeholder || ''}
                    className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition-all placeholder:text-white/20"
                    onChange={(e) => handleChange(field.name, e.target.value)}
                    value={formData[field.name] || ''}
                />
                )}
            </div>
          );
        })}
      </div>

      {/* Feedback de Status */}
      {status === 'success' && (
        <div className="p-4 bg-green-900/40 text-green-300 rounded border border-green-500/50 text-center font-bold font-rajdhani animate-fade-in">
          ✅ Mensagem enviada com sucesso! Entraremos em contato.
        </div>
      )}
      {status === 'error' && (
        <div className="p-4 bg-red-900/40 text-red-300 rounded border border-red-500/50 text-center font-bold font-rajdhani animate-fade-in">
          ❌ Erro ao enviar. Verifique sua conexão e tente novamente.
        </div>
      )}

      <button
        type="submit"
        disabled={status === 'sending' || status === 'success'}
        className="w-full py-4 px-6 bg-brand-green hover:bg-white hover:text-brand-dark text-brand-dark font-bold uppercase tracking-widest rounded transition-all shadow-[0_0_15px_rgba(46,204,64,0.2)] hover:shadow-[0_0_25px_rgba(46,204,64,0.4)] font-rajdhani disabled:opacity-50 disabled:cursor-not-allowed mt-4"
      >
        {status === 'sending' ? (
            <span className="flex items-center justify-center gap-2">
                <svg className="animate-spin h-5 w-5 text-brand-dark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Enviando...
            </span>
        ) : 'Enviar Mensagem'}
      </button>
    </form>
  );
}