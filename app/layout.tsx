// app/layout.tsx

import "./globals.css";
import { Rajdhani, Montserrat, Roboto } from "next/font/google";
import { GoogleTagManager } from '@next/third-parties/google'; // <--- IMPORTAR
import { API_BASE_URL, IMG_BASE_URL } from "../utils/api";

const rajdhani = Rajdhani({ subsets: ["latin"], weight: ["300","400","500","600","700"], variable: "--font-rajdhani", display: "swap" });
const montserrat = Montserrat({ subsets: ["latin"], weight: ["300","400","500","600","700"], variable: "--font-montserrat", display: "swap" });
const roboto = Roboto({ subsets: ["latin"], weight: ["300","400","500","700"], variable: "--font-roboto", display: "swap" });

// Função auxiliar para buscar dados (Reutilizável para não duplicar código)
async function getSettings() {
  try {
    const res = await fetch(`${API_BASE_URL}/api_settings.php?t=${Date.now()}`, { next: { revalidate: 60 } });
    return await res.json();
  } catch (e) {
    console.error("Erro ao carregar settings", e);
    return { success: false, data: {} };
  }
}

export async function generateMetadata() {
  const json = await getSettings();
    
  if (json.success && json.data) {
      const cleanBaseUrl = IMG_BASE_URL.replace(/\/$/, "");
      const cleanPath = json.data.site_favicon?.startsWith('/') ? json.data.site_favicon : `/${json.data.site_favicon}`;
      
      const faviconUrl = json.data.site_favicon 
        ? `${cleanBaseUrl}${cleanPath}?v=${Date.now()}` 
        : "/favicon.ico";

      return {
        title: json.data.site_title || "André Ventura",
        description: json.data.site_description || "Desenvolvimento Web & WordPress",
        icons: {
          icon: faviconUrl,
          shortcut: faviconUrl,
          apple: faviconUrl,
        },
      };
  }
  
  return {
    title: "André Ventura",
    description: "Desenvolvimento Web Profissional",
    icons: { icon: "/favicon.ico" },
  };
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  // Chamamos a função de novo. O Next.js deduplica a requisição automaticamente se for a mesma URL/config.
  const settings = await getSettings();
  const gtmId = settings?.data?.google_tag_manager_id;

  return (
    <html lang="pt-BR">
      {/* Se existir ID, injeta o GTM. Se não, não renderiza nada. */}
      {gtmId && <GoogleTagManager gtmId={gtmId} />}
      
      <body className={`${rajdhani.variable} ${montserrat.variable} ${roboto.variable} antialiased overflow-x-hidden`}>
        {children}
      </body>
    </html>
  );
}