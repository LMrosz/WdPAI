FROM php:8.3-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN apk add --no-cache \
        zlib \
        libzip \
        libpng \
        libjpeg-turbo \
        postgresql-libs \
    && apk add --no-cache --virtual .build-deps \
        zlib-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        postgresql-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        opcache \
        zip \
        gd \
        bcmath \
        pgsql \
        pdo_pgsql \
    && apk del .build-deps

# Limity PHP (Lab 13): zdjęcie wina ma najwyżej 2 MB (sprawdza to też aplikacja), więc cały formularz
# nie potrzebuje więcej niż kilku MB; nginx przepuszcza do 3 MB (client_max_body_size).
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

# Xdebug (debugowanie krokowe w IDE); ustawienia w docker/php/xdebug.ini
RUN apk add --no-cache --virtual .xdebug-build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .xdebug-build-deps
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Ustawienia bezpieczeństwa: bez wyświetlania błędów na stronie, bez argumentów w śladzie wyjątku (Lab 12)
COPY docker/php/security.ini /usr/local/etc/php/conf.d/zz-security.ini

COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1
# zależności (PHPMailer) bez pakietów deweloperskich; przy uruchomieniu usługi php robi to też docker-compose,
# bo bind mount .:/app przykrywa katalog vendor/ z obrazu
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress
