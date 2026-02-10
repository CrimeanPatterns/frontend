# syntax=docker/dockerfile:1

FROM php:7.4 as php
WORKDIR /app
RUN apt-get update && apt-get install -y unzip libzip-dev \
	&& pecl install zip \
	&& docker-php-ext-enable zip \
    && rm -r /tmp/* /var/cache/*
RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
    && pecl install memcached-3.2.0 \
    && docker-php-ext-enable memcached \
    && rm -r /tmp/* /var/cache/*
RUN pecl install apcu \
    && docker-php-ext-enable apcu
COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN --mount=type=secret,id=composer_auth,target=/root/.composer/auth.json \
    --mount=type=cache,target=/root/.composer/cache \
    --mount=from=lib,target=web/lib \
    COMPOSER_CACHE_DIR=/root/.composer/cache composer install --no-scripts --ignore-platform-reqs --prefer-dist --no-autoloader
COPY web/kernel ./web/kernel
COPY web/schema ./web/schema
COPY web/wsdl/awardwallet ./web/wsdl/awardwallet
COPY web/api/awardwallet ./web/api/awardwallet
COPY app/config/parameters.yml.dist ./app/config/
RUN \
    --mount=from=lib,target=web/lib \
    ls -l web/lib && composer dumpautoload && composer run-script build-params
COPY . ./
RUN \
    --mount=from=lib,target=web/lib \
    --mount=from=engine,target=engine \
    php -d memory_limit=16g app/console bazinga:js-translation:dump --merge-domains --format=js web/assets

FROM node:18

WORKDIR /app
COPY package.json yarn.lock webpack-extension-client.config.js ./
RUN --mount=type=secret,id=npmrc,target=/root/.npmrc \
    --mount=type=cache,target=/root/.yarn \
  YARN_CACHE_FOLDER=/root/.yarn yarn install
COPY web ./web/
COPY assets ./assets/
COPY jest.* ./
COPY --from=php /app/web/assets/translations ./web/assets/translations/
RUN yarn run test
