#!/bin/sh
set -e

cd /var/www/html

# Evita erro de "dubious ownership" quando o código está montado por volume
if command -v git >/dev/null 2>&1; then
  git config --global --add safe.directory /var/www/html || true
fi

# Instala dependências quando vendor não existir
if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
  echo "[entrypoint] vendor ausente. Instalando dependências..."
  composer config --no-interaction audit.block-insecure false || true
  composer install --no-interaction --prefer-dist --ignore-platform-req=ext-zip || true
fi

# Corrige warning legado do PHP 7.3+ no zend-stdlib (continue dentro de switch)
if [ -f "/var/www/html/vendor/zendframework/zend-stdlib/src/ArrayObject.php" ]; then
  php -r '$f="/var/www/html/vendor/zendframework/zend-stdlib/src/ArrayObject.php"; $c=file_get_contents($f); $n=str_replace("case '\''protectedProperties'\'':\n                    continue;", "case '\''protectedProperties'\'':\n                    continue 2;", $c); if ($n !== $c) { file_put_contents($f, $n); echo "[entrypoint] Patch aplicado em zend-stdlib\\n"; }'
fi

exec apache2-foreground
