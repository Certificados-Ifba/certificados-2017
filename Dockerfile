FROM php:7.4-apache

# Instalar extensões necessárias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    git \
    unzip \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    gd \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar Apache
RUN a2enmod rewrite
RUN a2enmod headers

# Diretório de trabalho
WORKDIR /var/www/html

# Copiar projeto
COPY . .

# Entrypoint para bootstrap de dependências em ambiente de desenvolvimento
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permissões
RUN chown -R www-data:www-data /var/www/html

# Configurar document root para public_html
RUN sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public_html#g' /etc/apache2/sites-enabled/000-default.conf
RUN printf '\n<Directory /var/www/html/public_html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/public-html.conf \
    && a2enconf public-html

# Configurar .htaccess
RUN echo 'RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php/$1 [L]' > /var/www/html/public_html/.htaccess

EXPOSE 80

CMD ["entrypoint.sh"]
