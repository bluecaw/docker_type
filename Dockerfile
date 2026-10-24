FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY ./src/ /var/www/html/

EXPOSE 80
