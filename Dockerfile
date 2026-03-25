FROM php:7.4-apache

ARG DEBIAN_FRONTEND=noninteractive

# Instala dependências do sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    gd \
    xml \
    soap \
 && a2enmod rewrite headers expires \
 && rm -rf /var/lib/apt/lists/*

# Define DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Configuração do Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Composer (multi-stage)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Copia apenas arquivos necessários (melhor cache de build)
COPY composer.json composer.lock* ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-interaction \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader || true

# Copia o restante da aplicação
COPY . .

# Criação de diretórios necessários
RUN mkdir -p \
    data/log \
    data/temp \
    data/cache \
    public_html/assets/certificados/frente \
    public_html/assets/certificados/verso \
 && chown -R www-data:www-data /var/www/html/data /var/www/html/public_html/assets

# Segurança básica (evita execução de PHP em uploads)
RUN echo "<Directory /var/www/html/data>\n\
    php_admin_flag engine off\n\
</Directory>" > /etc/apache2/conf-available/security.conf \
 && a2enconf security

EXPOSE 80

CMD ["apache2-foreground"]