// components/ProjectSlider.tsx

'use client';

import React from 'react';
import Image from 'next/image';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import { ArrowUpRight, ArrowRight } from 'lucide-react';
import { IMG_BASE_URL } from '../utils/api';

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
    <div className="relative">
      <Swiper
        modules={[Navigation, Pagination, Autoplay]}
        spaceBetween={30}
        slidesPerView={1}
        navigation={{
          nextEl: '.custom-swiper-button-next',
          prevEl: '.custom-swiper-button-prev',
        }}
        pagination={{ clickable: true }}
        loop={true}
        autoplay={{ delay: 5000, disableOnInteraction: false }}
        breakpoints={{
          640: { slidesPerView: 1 },
          768: { slidesPerView: 2 },
          1024: { slidesPerView: 3 },
        }}
        // CORREÇÃO AQUI:
        // !pt-10: Espaço no topo para o card "subir" sem cortar
        // !pb-14: Espaço embaixo para sombra e paginação
        // !px-4: Espaço lateral para sombras não cortarem nas bordas
        className="my-swiper !pt-10 !pb-14 !px-4"
      >
        {data.map((project) => {
          const imageUrl = project.image_url.startsWith('http') 
            ? project.image_url 
            : `${IMG_BASE_URL}${project.image_url}`;

          return (
            // !h-auto garante que todos tenham a mesma altura baseada no maior
            <SwiperSlide key={project.id} className="!h-auto">
              <a
                href={project.link}
                target="_blank"
                rel="noopener noreferrer"
                className="group flex h-full"
              >
                {/* O card tem relative e overflow-visible para a sombra vazar, mas o conteúdo interno tem overflow-hidden */}
                <div className="relative flex flex-col w-full rounded-3xl border border-white/10 bg-[#023047]/50 backdrop-blur-sm transition-all duration-500 hover:border-[#2ECC40]/50 hover:shadow-2xl hover:shadow-[#2ECC40]/20 hover:-translate-y-3">
                  
                  {/* Container da Imagem com overflow hidden para o zoom funcionar dentro das bordas redondas */}
                  <div className="relative aspect-[4/3] w-full shrink-0 overflow-hidden rounded-t-3xl bg-black/50">
                    <Image
                      src={imageUrl}
                      alt={project.title}
                      fill
                      className="object-cover transition-transform duration-700 group-hover:scale-110"
                      sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-[#023047]/90 via-transparent to-transparent" />
                    
                    <div className="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                      <div className="bg-[#2ECC40] text-[#023047] p-3 rounded-full shadow-lg">
                        <ArrowUpRight size={20} />
                      </div>
                    </div>
                  </div>

                  {/* Conteúdo */}
                  <div className="p-8 flex flex-col flex-1">
                    <h3 className="text-2xl font-bold uppercase tracking-wider mb-3 group-hover:text-[#2ECC40] transition-colors font-rajdhani">
                      {project.title}
                    </h3>
                    
                    <p className="text-gray-300 text-sm leading-relaxed mb-6 font-montserrat">
                      {project.description}
                    </p>
                    
                    <div className="mt-auto flex items-center text-[#2ECC40] font-bold uppercase text-sm tracking-wider group-hover:text-white transition-colors font-rajdhani">
                      Acessar Projeto
                      <ArrowRight size={16} className="ml-2 group-hover:translate-x-2 transition-transform" />
                    </div>
                  </div>
                </div>
              </a>
            </SwiperSlide>
          );
        })}
      </Swiper>

      {/* Botões de navegação ajustados para não ficarem em cima do padding */}
      <button className="custom-swiper-button-prev absolute left-2 md:-left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-[#023047]/80 backdrop-blur-md border border-[#2ECC40]/40 text-[#2ECC40] flex items-center justify-center hover:bg-[#2ECC40] hover:text-[#023047] transition-all duration-300 shadow-xl">
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
      </button>
      <button className="custom-swiper-button-next absolute right-2 md:-right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-[#023047]/80 backdrop-blur-md border border-[#2ECC40]/40 text-[#2ECC40] flex items-center justify-center hover:bg-[#2ECC40] hover:text-[#023047] transition-all duration-300 shadow-xl">
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" /></svg>
      </button>
    </div>
  );
}