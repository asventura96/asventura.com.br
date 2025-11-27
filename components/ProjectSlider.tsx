// components/ProjectSlider.tsx

'use client';

import React from 'react';
import Image from 'next/image';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import { ArrowUpRight, ArrowRight } from 'lucide-react';
import { API_BASE_URL } from '../utils/api';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

interface Project {
  id: number;
  title: string;
  description: string;
  image_url: string;
  link: string;
}

interface ProjectSliderProps {
  data: Project[];
}

export default function ProjectSlider({ data }: ProjectSliderProps) {
  return (
    <div className="w-full relative px-2 sm:px-12 custom-project-slider">
      {/* USAMOS A CLASSE .custom-project-slider PARA GANHAR DO GLOBAL.CSS
         Isso isola os estilos deste componente.
      */}
      <style jsx global>{`
        /* Importação de emergência das fontes caso o global não esteja carregando */
        @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap');

        /* Força a fonte nos títulos dentro deste slider específico */
        .custom-project-slider h3, 
        .custom-project-slider .project-title {
          font-family: 'Rajdhani', sans-serif !important;
        }
        
        /* Força a fonte nos textos dentro deste slider específico */
        .custom-project-slider p,
        .custom-project-slider .project-desc, 
        .custom-project-slider .project-link {
          font-family: 'Roboto', sans-serif !important;
        }

        /* SOBRESCREVENDO O BOTÃO VERDE GIGANTE DO GLOBAL.CSS */
        /* Usamos !important para garantir que vença o global */
        .custom-project-slider .swiper-button-next, 
        .custom-project-slider .swiper-button-prev {
          background-color: rgba(2, 48, 71, 0.85) !important; /* Fundo escuro */
          color: #2ECC40 !important; /* Seta Verde */
          width: 48px !important;
          height: 48px !important;
          border-radius: 50% !important;
          border: 1px solid rgba(46, 204, 64, 0.3) !important;
          box-shadow: 0 4px 15px rgba(0,0,0,0.5) !important;
          transition: all 0.3s ease !important;
          z-index: 50;
        }

        /* Hover dos botões */
        .custom-project-slider .swiper-button-next:hover, 
        .custom-project-slider .swiper-button-prev:hover {
          background-color: #2ECC40 !important; /* Fica verde no hover */
          color: #023047 !important; /* Seta fica azul */
          transform: scale(1.1);
          box-shadow: 0 0 20px rgba(46, 204, 64, 0.5) !important;
        }

        /* Ajuste do tamanho do ícone da seta */
        .custom-project-slider .swiper-button-next::after, 
        .custom-project-slider .swiper-button-prev::after {
          font-size: 20px !important;
          font-weight: bold !important;
        }

        /* Paginação (Bolinhas) */
        .custom-project-slider .swiper-pagination-bullet {
          background: #ffffff !important;
          opacity: 0.3;
        }
        .custom-project-slider .swiper-pagination-bullet-active {
          background: #2ECC40 !important;
          opacity: 1;
          width: 24px;
          border-radius: 4px;
          transition: width 0.3s;
        }

        /* Esconde setas no mobile para não atrapalhar */
        @media (max-width: 640px) {
          .custom-project-slider .swiper-button-next, 
          .custom-project-slider .swiper-button-prev {
            display: none !important;
          }
        }
      `}</style>
      
      <Swiper
        modules={[Navigation, Pagination, Autoplay]}
        spaceBetween={24}
        slidesPerView={1}
        navigation
        pagination={{ clickable: true, dynamicBullets: true }}
        loop={true}
        autoplay={{
          delay: 5000,
          disableOnInteraction: false,
          pauseOnMouseEnter: true,
        }}
        breakpoints={{
          640: { slidesPerView: 1, spaceBetween: 20 },
          768: { slidesPerView: 2, spaceBetween: 24 },
          1024: { slidesPerView: 3, spaceBetween: 30 },
        }}
        className="!pb-16 !px-1" 
      >
        {data.map((project) => {
          const isExternalLink = project.image_url.startsWith('http');
          
          const resolvedImageUrl = isExternalLink
            ? project.image_url
            : `${API_BASE_URL}${project.image_url}`;

          return (
            <SwiperSlide key={project.id} className="!h-auto pt-2 pb-4">
              <a 
                href={project.link} 
                target="_blank" 
                rel="noopener noreferrer"
                className="group block h-full select-none outline-none"
              >
                {/* CARD PRINCIPAL */}
                <div className="bg-[#023047] h-full flex flex-col border border-[#2ECC40]/20 rounded-2xl overflow-hidden transition-all duration-300 group-hover:border-[#2ECC40] group-hover:shadow-[0_10px_30px_-10px_rgba(46,204,64,0.2)] group-hover:-translate-y-1">
                  
                  {/* IMAGEM */}
                  <div className="relative h-56 w-full overflow-hidden bg-slate-900 border-b border-white/5">
                    <Image
                      src={resolvedImageUrl}
                      alt={project.title}
                      fill
                      className="object-cover transition-transform duration-700 group-hover:scale-110 opacity-90 group-hover:opacity-100"
                      sizes="(max-width: 768px) 100vw, 33vw"
                      unoptimized={true}
                    />
                    
                    {/* Overlay Gradiente */}
                    <div className="absolute inset-0 bg-gradient-to-t from-[#023047] via-transparent to-transparent opacity-80"></div>
                    
                    {/* Ícone Flutuante */}
                    <div className="absolute top-3 right-3 bg-[#2ECC40] text-[#023047] p-2 rounded-full opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300 shadow-lg font-bold z-10">
                      <ArrowUpRight size={18} />
                    </div>
                  </div>

                  {/* CONTEÚDO */}
                  <div className="p-6 flex flex-col flex-grow relative bg-[#023047]">
                    
                    {/* Linha decorativa no topo do texto */}
                    <div className="absolute top-0 left-6 right-6 h-px bg-gradient-to-r from-transparent via-[#2ECC40]/30 to-transparent opacity-50"></div>

                    {/* Título */}
                    <h3 className="project-title text-xl font-bold text-white uppercase mb-3 group-hover:text-[#2ECC40] transition-colors line-clamp-1 tracking-wide">
                      {project.title}
                    </h3>
                    
                    {/* Descrição */}
                    <p className="project-desc text-gray-300 text-sm leading-relaxed line-clamp-3 mb-6 flex-grow font-light">
                      {project.description}
                    </p>

                    {/* Botão Acessar */}
                    <div className="mt-auto pt-4 border-t border-white/10 flex items-center justify-start">
                       <span className="project-link text-xs font-bold text-[#2ECC40] uppercase tracking-widest group-hover:text-white transition-colors flex items-center gap-2">
                         Acessar Projeto 
                         <ArrowRight size={14} className="transition-transform group-hover:translate-x-1"/>
                       </span>
                    </div>
                  </div>
                </div>
              </a>
            </SwiperSlide>
          );
        })}
      </Swiper>
    </div>
  );
}