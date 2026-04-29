DOCKER_COMPOSE=docker compose
APP_SERVICE=app

up:
	$(DOCKER_COMPOSE) up -d --build

wait-db:
	$(DOCKER_COMPOSE) up -d db
	$(DOCKER_COMPOSE) exec -T db sh -c 'until mariadb-admin ping -h 127.0.0.1 -uroot -proot --silent; do sleep 1; done'

down:
	$(DOCKER_COMPOSE) down

destroy:
	$(DOCKER_COMPOSE) down -v --remove-orphans

composer-install:
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) composer install --working-dir=backend

test:
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) composer --working-dir=backend test

test-coverage:
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) composer --working-dir=backend test:coverage

lint:
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) composer --working-dir=backend lint

lint-fix:
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) composer --working-dir=backend lint:fix

phpstan:
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) composer --working-dir=backend phpstan

migrate:
	$(MAKE) wait-db
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) php backend/database/migrations/run.php

seed:
	$(MAKE) wait-db
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) php backend/database/seeders/run.php
