import ProjectSlider from '../components/ProjectSlider';

interface Project {
  id: number;
  title: string;
  description: string;
  image_url: string;
  link: string;
}

async function getProjects() {
  const apiUrl = 'http://localhost:8000/api_projects.php';
  try {
    const res = await fetch(apiUrl, { cache: 'no-store', next: { revalidate: 0 } });
    if (!res.ok) throw new Error('Falha na API');
    return res.json();
  } catch (error) {
    return [];
  }
}

export default async function Home() {
  const projects: Project[] = await getProjects();

  return (
    // GARANTIA DE FUNDO ESCURO
    <div className="min-h-screen bg-[#023047] text-white selection:bg-[#2ECC40] selection:text-[#023047]">
      
      {/* HEADER FIXO */}
      <header className="w-full py-5 px-4 sm:px-8 border-b border-white/10 bg-[#023047]/95 sticky top-0 z-50 backdrop-blur-md">
        <div className="max-w-7xl mx-auto flex justify-between items-center">
          {/* Logo */}
          <div className="flex flex-col leading-none">
            <span className="text-2xl font-bold tracking-widest text-white font-rajdhani uppercase">André</span>
            <div className="flex items-center gap-2">
              <div className="h-0.5 w-6 bg-[#2ECC40]"></div>
              <span className="text-xl font-bold tracking-widest text-[#89D6FB] font-rajdhani uppercase">Ventura</span>
            </div>
          </div>

          {/* Contato */}
          <div className="flex items-center gap-6">
            <div className="hidden md:block text-right text-xs sm:text-sm text-[#89D6FB] font-roboto">
              <p>+55 (31) 9 9190-4415</p>
              <p>contato@asventura.com.br</p>
            </div>
            <a href="#contact" className="bg-[#2ECC40] hover:bg-white hover:text-[#023047] text-[#023047] font-bold py-3 px-5 rounded shadow-[0_0_15px_rgba(46,204,64,0.4)] transition-all uppercase text-xs sm:text-sm tracking-wide font-rajdhani">
              Fale Conosco
            </a>
          </div>
        </div>
      </header>

      <main className="w-full">
        
        {/* HERO SECTION (BANNER) */}
        <section className="relative w-full h-[600px] flex items-center justify-start overflow-hidden">
          {/* Fundo da Imagem */}
          <div className="absolute inset-0 z-0 bg-[#023047]">
             {/* Se não tiver a imagem, ele fica azul escuro. Se tiver, aplica o efeito */}
            <img 
              src="/images/landing.jpg" 
              alt="Background Tech" 
              className="w-full h-full object-cover opacity-20 mix-blend-overlay" 
            />
            {/* Gradiente para o texto ficar legível */}
            <div className="absolute inset-0 bg-gradient-to-r from-[#023047] via-[#023047]/80 to-transparent"></div>
          </div>

          <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-8 w-full">
            <span className="text-[#FF8D37] font-bold font-roboto text-sm tracking-wider uppercase mb-4 block border-l-4 border-[#FF8D37] pl-3">
              André Ventura | Full Stack Developer
            </span>
            <h1 className="text-4xl sm:text-6xl font-semibold text-white leading-[1.1] max-w-3xl font-rajdhani">
              Transformando suas ideias em <br/>
              experiências digitais <span className="text-[#2ECC40]">incríveis.</span>
            </h1>
            <p className="mt-6 text-[#D4F0FC]/80 text-lg max-w-xl font-light font-montserrat">
              Soluções de engenharia robustas, interfaces modernas e automação inteligente para escalar o seu negócio.
            </p>
          </div>
        </section>

        {/* PROJETOS */}
        <section id="projects" className="py-24 bg-[#023047] relative border-t border-white/5">
          <div className="max-w-7xl mx-auto px-4 sm:px-8">
            <div className="flex items-center gap-4 mb-16">
              <h2 className="text-3xl font-bold text-white uppercase whitespace-nowrap font-rajdhani">
                Nossos Projetos
              </h2>
              {/* Linha Decorativa Verde */}
              <div className="h-px w-full bg-gradient-to-r from-[#2ECC40] to-transparent opacity-50 mt-1"></div>
            </div>
            
            {projects.length === 0 ? (
              <p className="text-center text-[#89D6FB]/50 py-10 border border-dashed border-[#89D6FB]/20 rounded font-roboto">
                Nenhum projeto cadastrado no momento.
              </p>
            ) : (
              <ProjectSlider data={projects} />
            )}
          </div>
        </section>

        {/* CONTATO */}
        <section id="contact" className="py-24 bg-[#012233] border-t border-white/5">
          <div className="max-w-3xl mx-auto px-4">
            <h2 className="text-4xl font-bold text-center mb-10 text-white uppercase font-rajdhani">
              Vamos conversar?
            </h2>
            <form className="space-y-6 bg-[#023047] p-8 sm:p-12 rounded-xl border border-white/5 shadow-2xl">
               <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-bold text-[#89D6FB] mb-2 uppercase font-rajdhani">Nome</label>
                    <input type="text" className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-[#2ECC40] focus:ring-1 focus:ring-[#2ECC40] transition-all" />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-[#89D6FB] mb-2 uppercase font-rajdhani">Email</label>
                    <input type="email" className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-[#2ECC40] focus:ring-1 focus:ring-[#2ECC40] transition-all" />
                  </div>
               </div>
              <div>
                <label className="block text-sm font-bold text-[#89D6FB] mb-2 uppercase font-rajdhani">Mensagem</label>
                <textarea rows={4} className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-[#2ECC40] focus:ring-1 focus:ring-[#2ECC40] transition-all"></textarea>
              </div>
              <button type="submit" className="w-full py-4 px-6 bg-[#2ECC40] hover:bg-white hover:text-[#023047] text-[#023047] font-bold uppercase tracking-widest rounded transition-all shadow-lg hover:shadow-[#2ECC40]/20 font-rajdhani">
                Enviar Mensagem
              </button>
            </form>
          </div>
        </section>

      </main>
    </div>
  );
}