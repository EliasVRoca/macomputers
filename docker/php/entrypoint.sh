#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Limpia cache de config antes de arrancar (como root) y luego restaura permisos
php artisan config:clear >/dev/null 2>&1 || true

if [ ! -L public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec "$@"
