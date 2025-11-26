import type { Metadata } from "next";
import { Rajdhani, Montserrat, Roboto } from "next/font/google";
import "./globals.css";
import { getSettings, API_BASE_URL } from "../utils/api";

// Carregando as fontes com as variáveis certas
const rajdhani = Rajdhani({
  subsets: ["latin"],
  weight: ["300", "400", "500", "600", "700"],
  variable: "--font-rajdhani",
  display: "swap",
});

const montserrat = Montserrat({
  subsets: ["latin"],
  weight: ["300", "400", "500", "600", "700"],
  variable: "--font-montserrat",
  display: "swap",
});

const roboto = Roboto({
  subsets: ["latin"],
  weight: ["300", "400", "500", "700"],
  variable: "--font-roboto",
  display: "swap",
});

export async function generateMetadata(): Promise<Metadata> {
  const settings = await getSettings();
  const iconUrl = settings.site_favicon ? `${API_BASE_URL}${settings.site_favicon}` : '/favicon.ico';

  return {
    title: settings.site_title || "André Ventura | Full Stack Developer",
    description: settings.site_description || "Soluções digitais de alta performance.",
    icons: { icon: iconUrl },
  };
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR">
      <body className={`${rajdhani.variable} ${montserrat.variable} ${roboto.variable} antialiased bg-brand-dark text-white`}>
        {children}
      </body>
    </html>
  );
}