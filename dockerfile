FROM php:8.4-fpm

# System tools and libraries PHP extensions need
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer from its official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www