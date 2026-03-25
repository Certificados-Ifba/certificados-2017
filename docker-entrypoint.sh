#!/bin/sh
set -e

# Garante pastas de upload (volume pode vir vazio ou com dono errado apos mount)
mkdir -p /var/www/html/public_html/assets/certificados/frente \
         /var/www/html/public_html/assets/certificados/verso \
         /var/www/html/data/log

chown -R www-data:www-data /var/www/html/public_html/assets/certificados \
  /var/www/html/data 2>/dev/null || true
chmod -R 775 /var/www/html/public_html/assets/certificados 2>/dev/null || true

exec docker-php-entrypoint "$@"
