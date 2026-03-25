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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN if [ -f composer.lock ]; then \
      composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; \
    else \
      composer config audit.block-insecure false; \
      composer update --no-dev --no-interaction --prefer-dist --optimize-autoloader; \
    fi

RUN sed -ri -e 's!/var/www/html!/var/www/html/public_html!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public_html!g' /etc/apache2/apache2.conf \
    && { \
      echo '<Directory /var/www/html/public_html>'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/app.conf \
    && a2enconf app \
    && { \
      echo 'ServerName localhost'; \
    } > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && { \
      echo 'display_errors=Off'; \
      echo 'log_errors=On'; \
      echo 'error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT & ~E_WARNING'; \
    } > /usr/local/etc/php/conf.d/99-app.ini

RUN mkdir -p /var/www/html/data/log && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
