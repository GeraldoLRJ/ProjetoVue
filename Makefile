SHELL := /bin/bash

.PHONY: init up down restart logs ps perm artisan composer tinker fresh

init:
	chmod +x bin/*.sh || true
	./bin/init.sh

up:
	docker compose up -d --build

down:
	docker compose down

restart: down up

logs:
	docker compose logs -f --tail=100

ps:
	docker compose ps

perm:
	./bin/perm.sh

artisan:
	docker compose run --rm artisan $(cmd)

composer:
	docker compose run --rm composer $(args)

tinker:
	docker compose run --rm artisan tinker

fresh:
	docker compose run --rm artisan migrate:fresh --seed
