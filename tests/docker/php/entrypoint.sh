#!/bin/sh

set -eu

flock tests/runtime/composer-install.lock composer update --prefer-dist --no-interaction

php --version
set -x
exec "$@"
