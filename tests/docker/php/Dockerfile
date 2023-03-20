ARG PHP_VERSION
FROM php:${PHP_VERSION}-cli-alpine

RUN apk --no-cache add linux-headers

# install xDebug
ENV XDEBUG_MODE=coverage
ENV XDEBUG_CONFIG=""
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
    && docker-php-ext-install pcntl sockets \
    && pecl install xdebug pcov \
    && docker-php-ext-enable xdebug pcov \
    && pecl clear-cache \
    && apk del .build-dependencies

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /var/www

CMD ["sleep", "infinity"]
