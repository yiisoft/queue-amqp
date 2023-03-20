export COMPOSE_PROJECT_NAME=yii-queue-amqp

build:
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml up -d --build

down:
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml down

test:
	docker-compose -f tests/docker-compose.yml build --pull php$(v)
	docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/phpunit --colors=always -v --debug
	docker-compose -f tests/docker-compose.yml down

run:
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml run php$(v)

mutation-test:
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml build --pull php$(v)
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml run php$(v) php -dpcov.enabled=1 -dpcov.directory=. vendor/bin/roave-infection-static-analysis-plugin -j2 --ignore-msi-with-no-mutations --only-covered
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml down

coverage:
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/phpunit --coverage-clover coverage.xml
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml down

static-analyze:
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/psalm --config=psalm.xml --shepherd --stats --php-version=8.0
	COMPOSE_PROJECT_NAME=yii-queue-amqp docker-compose -f tests/docker-compose.yml down
