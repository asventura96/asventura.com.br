// components/ProjectsArea.tsx

'use client'; // Isso diz que roda no navegador do usuário

import { useEffect, useState } from 'react';
import ProjectSlider from './ProjectSlider';
import { API_BASE_URL, Project } from '../utils/api';

export default function ProjectsArea() {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadProjects() {
      try {
        // O fetch acontece no computador do usuário, conectando na Hostinger
        // O ?t=... evita que o navegador guarde cache velho
        const res = await fetch(`${API_BASE_URL}/api_projects.php?t=${Date.now()}`);
        
        if (!res.ok) throw new Error('Erro na API');
        
        const data = await res.json();
        
        // Garante que é um array antes de salvar
        if (Array.isArray(data)) {
            setProjects(data);
        }
      } catch (error) {
        console.error("Falha ao buscar projetos:", error);
      } finally {
        setLoading(false);
      }
    }

    loadProjects();
  }, []);

  // Estado de Carregamento (Loading)
  if (loading) {
    return (
      <div className="flex justify-center items-center py-20">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#2ECC40]"></div>
      </div>
    );
  }

  // Estado Vazio
  if (projects.length === 0) {
    return (
      <p className="text-center text-[#89D6FB]/50 py-10 border border-dashed border-[#89D6FB]/20 rounded font-roboto">
        Nenhum projeto encontrado. (Verifique a conexão com a API)
      </p>
    );
  }

  // Sucesso: Mostra o Slider
  return <ProjectSlider data={projects} />;
}