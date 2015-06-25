project = profiles

all: build

install-deps:
	docker-compose -p $(project) run --no-deps profiles composer install

update-deps:
	docker-compose -p $(project) run --no-deps profiles composer update

run: 
	docker-compose -p $(project) up

test:
	docker-compose -p $(project) run -e PHP_ENV=test profiles vendor/bin/phpunit tests

test-features:
	docker-compose -p $(project) run -e PHP_ENV=test profiles vendor/bin/behat

build:
	docker build -t cncflora/$(project) .

push:
	docker push cncflora/$(project)

