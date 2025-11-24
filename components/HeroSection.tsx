// components/HeroSection.tsx
import Image from 'next/image';

export default function HeroSection() {
  return (
    <section className="bg-zinc-50 dark:bg-black py-20 sm:py-32">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col items-center text-center">
          <div className="mb-6">
            {/* Imagem de perfil - O usuário precisará fornecer a imagem real */}
            <div className="w-32 h-32 rounded-full overflow-hidden mx-auto bg-gray-200 dark:bg-zinc-700 flex items-center justify-center">
              <span className="text-4xl text-gray-500">AV</span>
            </div>
          </div>
          <h1 className="text-4xl sm:text-5xl font-extrabold tracking-tight text-black dark:text-white mb-4">
            André da Silva Ventura
          </h1>
          <h2 className="text-xl sm:text-2xl font-medium text-blue-600 dark:text-blue-400 mb-6">
            Engenheiro da Computação | Analista de Sistemas | Automação de Processos
          </h2>
          
          <p className="max-w-3xl text-lg text-zinc-600 dark:text-zinc-400 mb-8">
            Movido pela curiosidade e pela vontade de transformar ideias em soluções. Recém-graduado em Engenharia da Computação, com vivência prática em desenvolvimento de sistemas e suporte técnico. Foco em otimizar processos e explorar novos caminhos na tecnologia.
          </p>

          <div className="flex flex-wrap justify-center gap-4">
            <span className="px-4 py-2 bg-gray-200 dark:bg-zinc-800 text-black dark:text-white rounded-full text-sm font-medium">Python</span>
            <span className="px-4 py-2 bg-gray-200 dark:bg-zinc-800 text-black dark:text-white rounded-full text-sm font-medium">PHP</span>
            <span className="px-4 py-2 bg-gray-200 dark:bg-zinc-800 text-black dark:text-white rounded-full text-sm font-medium">Next.js</span>
            <span className="px-4 py-2 bg-gray-200 dark:bg-zinc-800 text-black dark:text-white rounded-full text-sm font-medium">SQL</span>
            <span className="px-4 py-2 bg-gray-200 dark:bg-zinc-800 text-black dark:text-white rounded-full text-sm font-medium">Automação</span>
          </div>
        </div>
      </div>
    </section>
  );
}
