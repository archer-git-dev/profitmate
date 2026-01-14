FROM php:8.4-fpm

# 1. Заменил mariadb-client на libpq-dev и pdo_mysql на pdo_pgsql
#    Добавил libzip-dev (нужен для zip, который просит composer)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgd-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    librabbitmq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl xml soap

# 2. Добавляем ClickHouse dependencies (bcmath, sockets)
RUN docker-php-ext-install bcmath sockets

# Твой Redis (оставляем как есть)
RUN pecl install redis \
    && docker-php-ext-enable redis

RUN pecl install amqp \
    && docker-php-ext-enable amqp

# Exif (оставляем)
RUN docker-php-ext-configure exif
RUN docker-php-ext-install exif
RUN docker-php-ext-enable exif

# GD (оставляем твою конфигурацию)
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-webp=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd

# Утилиты (оставляем)
RUN apt install jpegoptim optipng pngquant gifsicle libavif-bin -y

# Composer (оставляем)
RUN apt-get install -y wget curl
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

COPY ./conf.d /usr/local/etc/php/conf.d/

WORKDIR /var/www
