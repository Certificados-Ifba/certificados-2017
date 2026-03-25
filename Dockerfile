FROM php:7.4-apache

ARG DEBIAN_FRONTEND=noninteractive

# =============================
# Instala dependências do sistema
# =============================
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

# =============================
# Configuração do Apache
# =============================
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# =============================
# Composer (multi-stage)
# =============================
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# =============================
# Copia apenas composer.json (lock está inconsistente no repositório)
# =============================
COPY composer.json ./

# =============================
# Instala dependências (legado)
# =============================
RUN COMPOSER_ALLOW_SUPERUSER=1 composer config --global audit.block-insecure false \
 && COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-interaction \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader \
    --ignore-platform-reqs \
    --no-security-blocking \
 || (echo "❌ ERRO NO COMPOSER INSTALL" && exit 1)

# =============================
# Copia aplicação completa
# =============================
COPY . .

# =============================
# Garante que vendor existe (debug)
# =============================
RUN if [ ! -d "vendor" ]; then \
        echo "❌ Pasta vendor NÃO foi criada!" && exit 1; \
    fi

# =============================
# Criação de diretórios obrigatórios
# =============================
RUN mkdir -p \
    data/log \
    data/temp \
    data/cache \
    public_html/assets/certificados/frente \
    public_html/assets/certificados/verso

# =============================
# Permissões
# =============================
RUN chown -R www-data:www-data /var/www/html/data \
    /var/www/html/public_html/assets

# =============================
# Segurança básica (evita execução de PHP em uploads)
# =============================
RUN echo "<Directory /var/www/html/data>\n\
    php_admin_flag engine off\n\
</Directory>" > /etc/apache2/conf-available/security.conf \
 && a2enconf security

EXPOSE 80

CMD ["apache2-foreground"]