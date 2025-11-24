// utils/api.ts

// URL base da API PHP.
// Em produção, esta URL será o domínio do seu site (ex: https://asventura.com.br/backend/api)
// Para o desenvolvimento local, usaremos uma URL fictícia, mas o Next.js fará a busca
// diretamente no build, então a URL real será importante no momento do deploy.
const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/backend/api';

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

/**
 * Busca a lista de projetos da API PHP.
 * @returns Uma promessa que resolve para um array de projetos.
 */
export async function getProjects(): Promise<Project[]> {
  // Nota: No ambiente de build do Next.js (getStaticProps), a requisição será feita
  // diretamente, sem a necessidade de um servidor rodando.
  // Para simular a API PHP localmente, você precisaria de um servidor web (como Apache/Nginx)
  // rodando o PHP. Como estamos no ambiente de build, vamos simular a resposta
  // com dados mockados para o desenvolvimento inicial, e o usuário precisará
  // configurar a URL correta no deploy.

  // Para fins de demonstração e para que o Next.js não quebre no build,
  // vamos retornar um array vazio ou mockado se a API_BASE_URL não for acessível.
  try {
    // Esta URL DEVE ser a URL pública onde o seu backend PHP estará hospedado.
    // Ex: https://asventura.com.br/backend/api/projects.php
    const response = await fetch(`${API_BASE_URL}/projects.php`);
    
    if (!response.ok) {
      throw new Error(`Erro ao buscar projetos: ${response.statusText}`);
    }

    const result = await response.json();
    if (result.success) {
      return result.data as Project[];
    } else {
      console.error('API Error:', result.message);
      return [];
    }
  } catch (error) {
    console.error('Erro ao conectar com a API de Projetos:', error);
    // Retorna dados mockados para que o frontend possa ser desenvolvido
    return [
      { id: 1, title: 'Projeto Mock 1', description: 'Descrição do projeto mock 1.', image_url: '/images/mock1.jpg', link: '#' },
      { id: 2, title: 'Projeto Mock 2', description: 'Descrição do projeto mock 2.', image_url: '/images/mock2.jpg', link: '#' },
    ];
  }
}

/**
 * Envia os dados do formulário de contato para a API PHP.
 * @param data Os dados do formulário.
 * @returns Uma promessa que resolve para um objeto de sucesso/erro.
 */
export async function submitContactForm(data: ContactForm): Promise<{ success: boolean; message: string }> {
  try {
    const response = await fetch(`${API_BASE_URL}/contact.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    return result as { success: boolean; message: string };
  } catch (error) {
    console.error('Erro ao enviar formulário:', error);
    return { success: false, message: 'Erro de conexão com o servidor.' };
  }
}
