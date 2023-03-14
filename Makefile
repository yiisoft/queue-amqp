build:
	COMPOSE_FILE=tests/docker-compose.yml docker compose build

test:
	COMPOSE_FILE=tests/docker-compose.yml docker compose up -d

php:
	COMPOSE_FILE=tests/docker-compose.yml docker-compose run php81
