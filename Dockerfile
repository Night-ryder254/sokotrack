# syntax=docker/dockerfile:1
#
# SokoTrack production image: nginx + PHP-FPM + the Laravel scheduler, run by supervisord.
# Built by Render from render.yaml. Stages: base -> vendor + assets -> app.

ARG PHP_VERSION=8.4

# ---------------------------------------------------------------------------
# base: PHP plus the extensions the app needs. Composer resolves against this
# same PHP, so what is tested at build time is what runs in production.
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

# PHP2Shell (CVE-2026-109618 and CVE-2026-109619): refuse to build on an unpatched PHP,
# for example a stale cached base layer.
COPY deploy/php-version-guard.php /tmp/php-version-guard.php
RUN php /tmp/php-version-guard.php && rm /tmp/php-version-guard.php

# apk upgrade keeps the system libraries (libcurl, brotli, openssl) on their latest patches.
# hadolint ignore=DL3018
RUN apk upgrade --no-cache \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql \
 && (php -r 'exit(extension_loaded("Zend OPcache") ? 0 : 1);' || docker-php-ext-install -j"$(nproc)" opcache)

# ---------------------------------------------------------------------------
# vendor: Composer dependencies, production only, with a class map for fast autoloading.
# (An authoritative class map also means a file with the wrong letter case fails the build.)
# ---------------------------------------------------------------------------
FROM base AS vendor
# hadolint ignore=DL3018
RUN apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --no-scripts --classmap-authoritative \
 && (php artisan package:discover --ansi || echo "package:discover skipped; the manifest is built on first boot")

# ---------------------------------------------------------------------------
# assets: compile Tailwind and Alpine with Vite. vendor/ is needed because the Tailwind
# config also scans Laravel's pagination views for class names.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------------------
# app: the image that runs on Render
# ---------------------------------------------------------------------------
FROM base AS app
# hadolint ignore=DL3018
RUN apk add --no-cache nginx supervisor su-exec

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build
COPY deploy/php.ini /usr/local/etc/php/conf.d/zz-sokotrack.ini
COPY deploy/ /opt/deploy/

RUN chmod +x /opt/deploy/*.sh \
 && cp /opt/deploy/supervisord.conf /etc/supervisord.conf \
 && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && php artisan storage:link \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
ENTRYPOINT ["/opt/deploy/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
