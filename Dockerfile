FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        intl \
        zip \
        gd \
        mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN sed -ri -e 's!/var/www/html!/var/www/html/public_html!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public_html!g' /etc/apache2/apache2.conf \
    && { \
      echo '<Directory /var/www/html/public_html>'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/app.conf \
    && a2enconf app

RUN mkdir -p /var/www/html/data/log && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
