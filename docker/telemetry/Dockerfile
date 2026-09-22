FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libonig-dev libxml2-dev libpng-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mysqli mbstring dom gd zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN a2enmod rewrite
WORKDIR /var/www/html
