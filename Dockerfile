FROM php:7.2-cli

LABEL adaptedFrom="codeception/codeception dockerfile"

# Install required system packages
RUN apt-get update && \
    apt-get -y install \
            git \
            zlib1g-dev \
            libmemcached-dev \
            libpq-dev \
            libssl-dev \
            libxml2-dev \
            libzip-dev \
            unzip \
        --no-install-recommends && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install php extensions
RUN docker-php-ext-install \
    bcmath \
    pdo pdo_mysql pdo_pgsql \
    soap \
    sockets \
    zip

# Install pecl extensions
RUN pecl install \
        apcu \
        memcached \
        mongodb \
        soap \
        xdebug-2.9.5 && \
    docker-php-ext-enable \
        apcu.so \
        memcached.so \
        mongodb.so \
        soap.so \
        xdebug

# Configure php
COPY /test/codeception/docker/php/php.ini /usr/local/etc/php/conf.d/php.override.ini

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
        --filename=composer \
        --install-dir=/usr/local/bin

# Add source-code
COPY . /repo
RUN chmod -R 777 /repo/test/

RUN mkdir /testOutput
RUN chmod -R 777 /testOutput/

# Prepare application
WORKDIR /repo/test/codeception

# Install modules
RUN composer require --no-update \
codeception/module-asserts \
codeception/codeception && \
composer update --no-interaction --optimize-autoloader --apcu-autoloader





