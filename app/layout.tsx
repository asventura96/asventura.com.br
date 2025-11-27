// app/layout.tsx

import type { Metadata } from "next";
import { Rajdhani, Montserrat, Roboto } from "next/font/google";
import "./globals.css";
import { getSettings, API_BASE_URL } from "../utils/api";

// Configurando Fonte de Títulos (Rajdhani)
const rajdhani = Rajdhani({
  subsets: ["latin"],
  weight: ["300", "400", "500", "600", "700"],
  variable: "--font-rajdhani",
  display: "swap",
});

// Configurando Fonte de Texto (Montserrat)
const montserrat = Montserrat({
  subsets: ["latin"],
  weight: ["300", "400", "500", "600", "700"],
  variable: "--font-montserrat",
  display: "swap",
});

// Configurando Fonte Auxiliar (Roboto)
const roboto = Roboto({
  subsets: ["latin"],
  weight: ["300", "400", "500", "700"],
  variable: "--font-roboto",
  display: "swap",
});

export async function generateMetadata(): Promise<Metadata> {
  try {
    const settings = await getSettings();
    const iconUrl = settings?.site_favicon ? `${API_BASE_URL}${settings.site_favicon}` : '/favicon.ico';

    return {
      title: settings?.site_title || "André Ventura | Full Stack Developer",
      description: settings?.site_description || "Soluções digitais de alta performance.",
      icons: { icon: iconUrl },
    };
  } catch (error) {
    return {
      title: "André Ventura | Full Stack Developer",
      description: "Desenvolvimento Web Profissional.",
    };
  }
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR">
      {/* Aqui aplicamos as variáveis de fonte e o fundo AZUL (#023047) direto na raiz 
         para evitar telas brancas ou fontes erradas.
      */}
      <body className={`${rajdhani.variable} ${montserrat.variable} ${roboto.variable} antialiased bg-[#023047] text-white overflow-x-hidden`}>
        {children}
      </body>
    </html>
  );
}