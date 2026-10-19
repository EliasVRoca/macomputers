# syntax=docker/dockerfile:1

# ---------- Etapa 1: compilar assets con pnpm ----------
FROM node:24-alpine AS assets
WORKDIR /var/www/html
RUN corepack enable
COPY package.json .npmrc ./
RUN pnpm install
COPY . .
RUN pnpm build

# ---------- Etapa 2: PHP 8.5 + php-fpm ----------
FROM php:8.5-fpm-alpine AS php
RUN apk add --no-cache icu-libs libzip \
 && apk add --no-cache --virtual .build-deps icu-dev libzip-dev \
 && docker-php-ext-install pdo_mysql bcmath intl zip \
 && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=assets /var/www/html/public/build ./public/build

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --optimize-autoloader --prefer-dist

# Permisos de escritura para www-data (uid 82 en Alpine)
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwX storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---------- Etapa 2b: PHP + Node/pnpm para desarrollo ----------
# Solo la usa docker-compose.override.yml (target: php-dev).
FROM php AS php-dev
RUN apk add --no-cache nodejs npm \
 && npm install -g pnpm@9.15.9

# ---------- Etapa 3: nginx con el mismo código ----------
FROM nginx:alpine AS web
COPY --from=php /var/www/html /var/www/html
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
