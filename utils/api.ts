// utils/api.ts

export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export interface Project {
  id: number;
  title: string;
  description: string;
  image_url: string;
  link: string;
}

export interface ContactForm {
  name: string;
  email: string;
  message: string;
}

export interface SiteSettings {
  site_title: string;
  site_description: string;
  site_logo: string;
  site_favicon: string;
  [key: string]: string;
}

export async function getProjects(): Promise<Project[]> {
  try {
    // Busca projetos sem cache
    const response = await fetch(`${API_BASE_URL}/api_projects.php`, { cache: 'no-store' });
    if (!response.ok) throw new Error('Erro API');
    return await response.json();
  } catch (error) {
    return [];
  }
}

export async function submitContactForm(data: ContactForm): Promise<{ success: boolean; message: string }> {
  try {
    const response = await fetch(`${API_BASE_URL}/api_submit.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Erro de conexão.' };
  }
}

export async function getSettings(): Promise<SiteSettings> {
  try {
    // CORREÇÃO AQUI: Removido 'revalidate: 60' que causava o conflito
    const response = await fetch(`${API_BASE_URL}/api_settings.php`, { 
      cache: 'no-store'
    });
    
    const json = await response.json();
    return json.data || {};
  } catch (error) {
    console.error("Erro ao buscar settings", error);
    return { site_title: 'André Ventura', site_description: '', site_logo: '', site_favicon: '' };
  }
}