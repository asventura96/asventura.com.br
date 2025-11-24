/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export', // Habilita a exportação estática para compatibilidade com Hostinger
  distDir: 'out', // Diretório de saída para os arquivos estáticos
  images: {
    unoptimized: true, // Necessário para exportação estática
  },
};

module.exports = nextConfig;
