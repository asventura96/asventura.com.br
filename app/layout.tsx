// app/layout.tsx

import type { Metadata } from "next";
import { Rajdhani, Montserrat, Roboto } from "next/font/google";
import "./globals.css";
// Removi o import do getSettings e API_BASE_URL que estava causando erro

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

// Metadata simples e segura (sem await no server component se der pau)
export const metadata: Metadata = {
  title: "André Ventura | Desenvolvedor WordPress & Full Stack",
  description: "Criação de sites profissionais com WordPress, Elementor, Divi e WooCommerce.",
  icons: { icon: "/favicon.ico" },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="pt-BR">
      <body className={`${rajdhani.variable} ${montserrat.variable} ${roboto.variable} antialiased overflow-x-hidden`}>
        {children}
      </body>
    </html>
  );
}