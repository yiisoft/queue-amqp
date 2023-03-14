ARG PHP_VERSION
FROM php:${PHP_VERSION}-cli-alpine

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
    && pecl install xdebug pcov \
    && docker-php-ext-enable xdebug pcov \
    && pecl clear-cache \
    && apk del .build-dependencies

RUN curl -sS https://getcomposer.org/installer | php -- --2 --install-dir=/usr/local/bin --filename=composer

ADD . /app
WORKDIR /app
RUN composer install --prefer-dist --no-interaction
