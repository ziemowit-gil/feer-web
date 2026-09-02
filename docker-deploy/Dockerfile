# --- Etap 1: budowanie assetów (Vite/Tailwind) na znanej wersji Node ---
# Osobny etap zamiast pakietu nodejs z Alpine — Alpine trzyma starsze wersje
# Node, a Vite 8 wymaga nowszej; obraz node:22 daje pewność, że build się powiedzie.
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci --silent && npm run build

# --- Etap 2: aplikacja PHP ---
FROM php:8.4-fpm-alpine

# System deps
RUN apk add --no-cache \
        bash curl git zip unzip shadow \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libzip-dev \
        oniguruma-dev icu-dev

# PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" \
        pdo_sqlite \
        gd \
        mbstring \
        exif \
        bcmath \
        intl \
        zip \
        opcache \
        pcntl

# Opcache — zoptymalizowane pod produkcję (validate_timestamps=0 dla niezmiennych obrazów)
RUN printf "opcache.enable=1\nopcache.memory_consumption=128\nopcache.interned_strings_buffer=8\nopcache.max_accelerated_files=10000\nopcache.validate_timestamps=0\n" \
    > /usr/local/etc/php/conf.d/opcache.ini

# Upload limit
RUN printf "upload_max_filesize=64M\npost_max_size=64M\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- Zależności PHP (cache layer) ---
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-scripts \
        --quiet

# --- Reszta kodu aplikacji + zbudowane assety z etapu 1 ---
COPY . .
COPY --from=assets /app/public/build ./public/build

# Re-run post-install scripts (e.g. php-http)
RUN composer run-script post-autoload-dump --no-interaction --quiet 2>/dev/null || true

# Uprawnienia
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

COPY docker-deploy/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
