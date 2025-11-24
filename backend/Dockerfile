FROM php:8.2-apache

# Instala extensões do PHP para comunicar com MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita o mod_rewrite do Apache (essencial para rotas amigáveis/API)
RUN a2enmod rewrite