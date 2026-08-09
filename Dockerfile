FROM php:8.2-fpm-alpine

# Extensões nativas do Laravel + driver PostgreSQL (arquitectura-alvo, ver
# SIG-IIV-MEMORIA-PROJETO.md secção 15/16 — SQLite fica só para dev local).
RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        libpng-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        bcmath \
        zip \
        intl \
        opcache \
        gd \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/99-sig-iiv.ini

WORKDIR /var/www/html

# Camada de dependências isolada do código-fonte para tirar partido da cache
# do Docker — só reinstala quando composer.json/lock mudam.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
