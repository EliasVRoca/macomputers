#!/bin/sh
set -e

DB_HOST="${DB_HOST:-mariadb}"
DB_PORT="${DB_PORT:-3306}"

# ------------------------------------------------------------------
# 1. Esperar a MariaDB (fail-fast: si no hay BD, el contenedor avisa)
# ------------------------------------------------------------------
echo "→ Esperando MariaDB en ${DB_HOST}:${DB_PORT}..."
php -r '
$host = getenv("DB_HOST") ?: "mariadb";
$port = (int) (getenv("DB_PORT") ?: 3306);
for ($i = 0; $i < 60; $i++) {
    $conn = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($conn) { fclose($conn); exit(0); }
    sleep(1);
}
fwrite(STDERR, "MariaDB no alcanzable en {$host}:{$port}\n");
exit(1);
'

# Directorios que Laravel necesita y que pueden no existir en un clon limpio
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# El volumen nombrado de vendor nace como root:root
mkdir -p /var/www/html/vendor
chown www-data:www-data /var/www/html/vendor

# ------------------------------------------------------------------
# 2. Dependencias PHP (solo la primera vez o si cambió el lock)
# ------------------------------------------------------------------
if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/composer/installed.json ]; then
    echo "→ composer install"
    su -s /bin/sh www-data -c \
        'cd /var/www/html && HOME=/tmp COMPOSER_HOME=/tmp/composer composer install --no-interaction --prefer-dist'
fi

# ------------------------------------------------------------------
# 3. Enlace de storage público
# ------------------------------------------------------------------
# Enlace relativo: funciona dentro del contenedor y también desde el host
# (artisan storage:link --relative requiere symfony/filesystem, que no está instalado).
su -s /bin/sh www-data -c \
    'cd /var/www/html && ln -sfn ../storage/app/public public/storage' >/dev/null 2>&1 || true

# ------------------------------------------------------------------
# 4. Migraciones (AUTO_MIGRATE=false para desactivarlo)
# ------------------------------------------------------------------
if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    echo "→ php artisan migrate"
    su -s /bin/sh www-data -c 'cd /var/www/html && php artisan migrate --force'
fi

# ------------------------------------------------------------------
# 5. Limpiar cachés de ejecuciones anteriores
# ------------------------------------------------------------------
su -s /bin/sh www-data -c \
    'cd /var/www/html && php artisan optimize:clear' >/dev/null 2>&1 || true \
    '&& cd /var/www/html && php artisan storage:link' >/dev/null 2>&1 || true \
    
exec "$@"
