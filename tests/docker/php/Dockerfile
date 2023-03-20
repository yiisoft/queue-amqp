ARG PHP_VERSION
FROM php:${PHP_VERSION}-cli-alpine

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apk --no-cache add \
    git \
    bash \
    linux-headers \
    > /dev/null

# install xDebug
ENV XDEBUG_MODE=coverage
ENV XDEBUG_CONFIG=""
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
    && docker-php-ext-install pcntl sockets \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && pecl clear-cache \
    && apk del .build-dependencies

ADD . /app
WORKDIR /app
RUN composer install --prefer-dist --no-interaction
