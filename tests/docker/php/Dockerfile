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
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS
RUN docker-php-ext-install sockets
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
RUN pecl clear-cache \
    && apk del .build-dependencies

RUN curl -sS https://getcomposer.org/installer | php -- --2 --install-dir=/usr/local/bin --filename=composer

ADD . /app
WORKDIR /app
RUN composer install --prefer-dist --no-interaction
