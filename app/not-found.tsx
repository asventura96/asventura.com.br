import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="min-h-screen bg-brand-dark flex flex-col items-center justify-center text-white px-4 selection:bg-brand-green selection:text-brand-dark">
      
      {/* Título Grande com a Fonte Rajdhani */}
      <h1 className="text-9xl font-bold text-brand-green font-rajdhani drop-shadow-[0_0_15px_rgba(46,204,64,0.5)]">
        404
      </h1>

      <h2 className="text-3xl md:text-4xl font-bold mt-4 mb-6 text-center font-rajdhani">
        Página não encontrada
      </h2>

      <p className="text-brand-blue/80 text-lg text-center max-w-md font-montserrat mb-10">
        O caminho que você tentou acessar não existe ou foi movido. 
        Retorne à base para continuar navegando.
      </p>

      {/* Botão de Voltar */}
      <Link 
        href="/"
        className="px-8 py-4 bg-brand-green hover:bg-white hover:text-brand-dark text-brand-dark font-bold rounded shadow-[0_0_20px_rgba(46,204,64,0.3)] transition-all transform hover:scale-105 uppercase tracking-widest font-rajdhani"
      >
        Voltar ao Início
      </Link>

      {/* Detalhe Decorativo de Fundo */}
      <div className="absolute inset-0 pointer-events-none z-[-1] overflow-hidden">
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-brand-green/10 rounded-full blur-[100px]"></div>
      </div>

    </div>
  );
}