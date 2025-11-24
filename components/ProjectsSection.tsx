// components/ProjectsSection.tsx
import { getProjects, Project } from '@/utils/api';
import Link from 'next/link';

interface ProjectsSectionProps {
  projects: Project[];
}

export default function ProjectsSection({ projects }: ProjectsSectionProps) {
  return (
    <section id="projects" className="py-16 bg-gray-50 dark:bg-zinc-900">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-4xl font-bold text-center mb-12 text-black dark:text-white">Nossos Projetos</h2>
        
        {projects.length === 0 ? (
          <p className="text-center text-lg text-zinc-600 dark:text-zinc-400">
            Nenhum projeto encontrado. Adicione projetos através do Painel Administrativo.
          </p>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {projects.map((project) => (
              <div key={project.id} className="bg-white dark:bg-zinc-800 rounded-lg shadow-lg overflow-hidden transition-transform transform hover:scale-[1.02]">
                {project.image_url && (
                  <img 
                    src={project.image_url} 
                    alt={project.title} 
                    className="w-full h-48 object-cover"
                  />
                )}
                <div className="p-6">
                  <h3 className="text-xl font-semibold mb-2 text-black dark:text-white">{project.title}</h3>
                  <p className="text-zinc-600 dark:text-zinc-400 mb-4">{project.description}</p>
                  {project.link && (
                    <Link href={project.link} target="_blank" className="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                      Ver Projeto &rarr;
                    </Link>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
