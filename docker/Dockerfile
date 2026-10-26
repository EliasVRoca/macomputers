# syntax=docker/dockerfile:1

# Imagen SOLO para desarrollo: el código se monta con bind mount,
# las dependencias PHP se instalan en el entrypoint y Vite corre en el host.
FROM php:8.5-fpm-alpine

ARG UID=1000
ARG GID=1000

# ---- Paquetes del sistema ----
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    git \
    unzip \
    curl \
    tzdata \
    shadow \
    icu-dev \
    libzip-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    oniguruma-dev \
    libxml2-dev \
    $PHPIZE_DEPS

# ---- Extensiones PHP ----
# gd con webp + exif: los usa app/Services/Imagenes/DescargadorImagen.php
# OPcache ya viene compilado y habilitado en php:8.5-fpm-alpine;
# reinstalarlo rompe el build ("modules/*" no existe).
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        intl \
        gd \
        zip \
        pcntl \
        exif

# ---- www-data con el UID/GID del usuario del host (evita líos de permisos) ----
RUN groupmod -g ${GID} www-data \
    && usermod -u ${UID} -g www-data www-data

# ---- Composer ----
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# ---- Configuración ----
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
