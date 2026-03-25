FROM php:7.4-apache

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get -o Acquire::Retries=3 update && apt-get install -y --no-install-recommends \
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
 && docker-php-ext-install -j1 \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    gd \
    xml \
    soap \
 && a2enmod rewrite headers expires \
 && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN if [ ! -d vendor ]; then composer install --no-interaction --prefer-dist; fi \
 && mkdir -p \
    data/log \
    data/temp \
    data/cache \
    public_html/assets/certificados/frente \
    public_html/assets/certificados/verso \
 && chown -R www-data:www-data /var/www/html/data /var/www/html/public_html/assets/certificados

EXPOSE 80

CMD ["apache2-foreground"]
