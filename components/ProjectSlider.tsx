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
    <div className="w-full relative px-0 sm:px-4">
      
      {/* Estilos locais para as setas deste slider específico */}
      <style jsx global>{`
        .swiper-button-next, .swiper-button-prev {
          color: #2ECC40 !important;
          background: rgba(2, 48, 71, 0.9);
          width: 44px !important;
          height: 44px !important;
          border-radius: 50%;
          border: 1px solid rgba(46, 204, 64, 0.3);
          backdrop-filter: blur(4px);
          transition: all 0.3s ease;
          z-index: 50;
        }

        .swiper-button-next:hover, .swiper-button-prev:hover {
          background: #2ECC40 !important;
          color: #023047 !important;
          transform: scale(1.1);
          box-shadow: 0 0 15px rgba(46, 204, 64, 0.5);
        }

        .swiper-button-next::after, .swiper-button-prev::after {
          font-size: 18px !important;
          font-weight: bold;
        }

        @media (max-width: 640px) {
          .swiper-button-next, .swiper-button-prev {
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
          const resolvedImageUrl = isExternalLink ? project.image_url : `${API_BASE_URL}${project.image_url}`;

          return (
            <SwiperSlide key={project.id} className="!h-auto pt-2 pb-4">
              <a 
                href={project.link} 
                target="_blank" 
                rel="noopener noreferrer"
                className="group block h-full select-none outline-none"
              >
                <div className="bg-[#023047] h-full flex flex-col border border-[#2ECC40]/20 rounded-2xl overflow-hidden transition-all duration-300 group-hover:border-[#2ECC40] group-hover:shadow-[0_10px_30px_-10px_rgba(46,204,64,0.2)] group-hover:-translate-y-1">
                  
                  {/* IMAGEM */}
                  <div className="relative h-60 w-full overflow-hidden bg-slate-900 border-b border-white/5">
                    <Image
                      src={resolvedImageUrl}
                      alt={project.title}
                      fill
                      className="object-cover transition-transform duration-700 group-hover:scale-110 opacity-90 group-hover:opacity-100"
                      sizes="(max-width: 768px) 100vw, 33vw"
                      unoptimized={true}
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-[#023047] via-transparent to-transparent opacity-60"></div>
                    
                    {/* Badge flutuante */}
                    <div className="absolute top-3 right-3 bg-[#2ECC40] text-[#023047] p-2 rounded-full opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300 shadow-lg font-bold z-10">
                      <ArrowUpRight size={18} />
                    </div>
                  </div>

                  {/* CONTEÚDO */}
                  <div className="p-6 flex flex-col flex-grow relative bg-[#023047]">
                    
                    {/* Título - Forçando a fonte Rajdhani via variável CSS */}
                    <h3 className="text-2xl font-bold text-white uppercase mb-3 group-hover:text-[#2ECC40] transition-colors line-clamp-1 tracking-wide font-[family-name:var(--font-rajdhani)]">
                      {project.title}
                    </h3>
                    
                    {/* Descrição - Forçando a fonte Montserrat via variável CSS */}
                    <p className="text-gray-300 text-sm leading-relaxed line-clamp-3 mb-6 flex-grow font-light font-[family-name:var(--font-montserrat)]">
                      {project.description}
                    </p>

                    {/* Botão */}
                    <div className="mt-auto pt-4 border-t border-white/10 flex items-center justify-start">
                       <span className="text-xs font-bold text-[#2ECC40] uppercase tracking-widest group-hover:text-white transition-colors flex items-center gap-2 font-[family-name:var(--font-rajdhani)]">
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