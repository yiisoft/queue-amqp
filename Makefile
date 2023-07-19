export COMPOSE_PROJECT_NAME=yii-queue-amqp

help:			## Display help information
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

build:			## Build an image from a docker-compose file. Params: {{ v=8.1 }}. Default latest PHP 8.1
	docker-compose -f tests/docker-compose.yml up -d --build

down:			## Stop and remove containers, networks
	docker-compose -f tests/docker-compose.yml down

test:			## Run tests. Params: {{ v=8.1 }}. Default latest PHP 8.1
	docker-compose -f tests/docker-compose.yml build --pull php$(v)
	docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/phpunit --debug
	make down

sh:			## Enter the container with the application
	docker-compose -f tests/docker-compose.yml run php$(v)

mutation-test:		## Run mutation tests. Params: {{ v=8.1 }}. Default latest PHP 8.1
	docker-compose -f tests/docker-compose.yml build --pull php$(v)
	docker-compose -f tests/docker-compose.yml run php$(v) php -dpcov.enabled=1 -dpcov.directory=. vendor/bin/roave-infection-static-analysis-plugin -j2 --ignore-msi-with-no-mutations --only-covered
	make down

coverage:		## Run code coverage. Params: {{ v=8.1 }}. Default latest PHP 8.1
	docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/phpunit --coverage-clover coverage.xml
	make down

static-analyze:		## Run code static analyze. Params: {{ v=8.1 }}. Default latest PHP 8.1
	docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/psalm --config=psalm.xml --shepherd --stats --php-version=8.0
	make down
