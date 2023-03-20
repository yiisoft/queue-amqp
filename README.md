<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii Queue AMQP Adapter</h1>
    <br>
</p>

AMQP adapter based on [php-amqplib](https://github.com/php-amqplib/php-amqplib) package for [Yii Queue](https://github.com/yiisoft/yii-queue).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-queue-amqp/v/stable.png)](https://packagist.org/packages/yiisoft/yii-queue-amqp)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-queue-amqp/downloads.png)](https://packagist.org/packages/yiisoft/yii-queue-amqp)
[![Build status](https://github.com/yiisoft/yii-queue-amqp/workflows/build/badge.svg)](https://github.com/yiisoft/yii-queue-amqp/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-queue-amqp/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-queue-amqp/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-queue-amqp/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-queue-amqp/?branch=master)
[![static analysis](https://github.com/yiisoft/yii-queue-amqp/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-queue-amqp/actions?query=workflow%3A%22static+analysis%22)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-queue-amqp%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-queue-amqp/master)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-queue-amqp/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-queue-amqp)
### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
cd tests && docker compose build && docker compose run --rm php82 vendor/bin/phpunit
```

You can use any of the supported PHP versions with service names `php80`, `php81` and `php82`.  
To debug code with xDebug and to use volumes inside the built containers, you can use
`test/docker-compose.development.yml`. To do so you should either run
`docker compose -f docker-compose.yml -f docker-compose.development.yml run --rm php<version> vendor/bin/phpunit`
or copy [tests/.env.example](tests/.env.example) into `tests/.env` and run tests as usual.

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev). To run static analysis:

```php
./vendor/bin/psalm
```

## For Docker

If you are using Docker, then you have access to a set of prepared commands in the Makefile

### Static analysis

```bash
make static-analyze v=80
```

### Unit tests

```bash
make test v=80
```

### Mutation tests

```bash
make mutation-test v=80
```

### Code coverage

```bash
make coverage v=80
```
