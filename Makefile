build:
	COMPOSE_FILE=tests/docker-compose.yml docker compose build

test:
	COMPOSE_FILE=tests/docker-compose.yml docker compose up -d

php:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose run php81

mutation-test:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose build --pull php$(v)
	COMPOSE_FILE=tests/docker-compose.yml docker-compose run php$(v) php -dpcov.enabled=1 -dpcov.directory=. vendor/bin/roave-infection-static-analysis-plugin -j2 --ignore-msi-with-no-mutations --only-covered
	COMPOSE_FILE=tests/docker-compose.yml docker-compose down