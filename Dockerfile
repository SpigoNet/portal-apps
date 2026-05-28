# ==========================================
# ESTÁGIO 1: Dependências de Frontend (Node.js)
# ==========================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ==========================================
# ESTÁGIO 2: Aplicação Final de Produção (PHP Apache)
# ==========================================
FROM php:8.3-apache

# 1. Instalar dependências essenciais do sistema operacional e do PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip gd opcache \
    && apt-get clean && rm -rf /var/list/apt/lists/*

# 2. Habilitar mod_rewrite do Apache para rotas amigáveis do Laravel
RUN a2enmod rewrite

# 3. Alterar a raiz de documentos (DocumentRoot) do Apache para a pasta /public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Configurar arquivo php.ini padrão de produção
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Otimizações de OPcache para produção
RUN echo "opcache.memory_consumption=192" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.revalidate_freq=0" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini

# 5. Definir diretório de trabalho e copiar código do projeto
WORKDIR /var/www/html
COPY . .

# 6. Copiar os assets de frontend já compilados no Estágio 1
COPY --from=frontend-builder /app/public/build ./public/build

# 7. Instalar dependências do Composer (PHP) otimizadas para produção
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-scripts

# 8. Ajustar permissões cruciais do Laravel para o usuário do Apache (www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Porta exposta do Apache
EXPOSE 80