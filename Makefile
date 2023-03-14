build:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose up -d --build

test:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose build --pull php$(v)
	COMPOSE_FILE=tests/docker-compose.yml docker-compose run php$(v) vendor/bin/phpunit --colors=always
	COMPOSE_FILE=tests/docker-compose.yml docker-compose down

php:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose run php$(v)

mutation-test:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose build --pull php$(v)
	COMPOSE_FILE=tests/docker-compose.yml docker-compose run php$(v) php -dpcov.enabled=1 -dpcov.directory=. vendor/bin/roave-infection-static-analysis-plugin -j2 --ignore-msi-with-no-mutations --only-covered
	COMPOSE_FILE=tests/docker-compose.yml docker-compose down

coverage:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose run php81 vendor/bin/phpunit --coverage-clover coverage.xml
	COMPOSE_FILE=tests/docker-compose.yml docker-compose down
