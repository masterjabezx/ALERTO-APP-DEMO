FROM php:8.5-fpm-bookworm AS php-build

ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd opcache pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM php-build AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload --no-dev --optimize --no-scripts --no-interaction \
    && php artisan package:discover --ansi

FROM node:24-bookworm-slim AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor

RUN mkdir -p storage/framework/views \
    && npm run build

FROM php:8.5-fpm-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    PORT=10000 \
    TZ=UTC

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ffmpeg \
        gettext-base \
        gosu \
        libfreetype6 \
        libjpeg62-turbo \
        libpng16-16 \
        libwebp7 \
        libzip4 \
        nginx \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=php-build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=vendor /var/www/html/ /var/www/html/
COPY --from=frontend /var/www/html/public/build/ /var/www/html/public/build/

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/nginx/site.conf.template /etc/nginx/templates/default.conf.template
COPY docker/php/production.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod 0755 /usr/local/bin/entrypoint \
    && rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 10000
STOPSIGNAL SIGTERM

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["web"]
