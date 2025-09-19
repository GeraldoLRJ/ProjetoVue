# Projeto Dockerizado: Laravel 8 + Postgres + Redis

Este repositório contém a infraestrutura Docker para subir um projeto Laravel 8 chamado `backend`, com Postgres e Redis.

Componentes
- PHP-FPM 8.0 (app)
- Nginx (web)
- Postgres 14 (db)
- Redis 7 (redis)
- Composer (container utilitário)
- Artisan (container utilitário)

Pré-requisitos
- Docker e Docker Compose instalados

Passo a passo
1) Dar permissão de execução aos scripts:
	chmod +x bin/*.sh

2) Inicializar o projeto Laravel (cria `backend/` e o `.env`):
	./bin/init.sh

3) Subir os serviços:
	docker compose up -d --build

4) Acessar a aplicação:
	http://localhost:8080

5) Rodar comandos do Artisan (exemplos):
	docker compose run --rm artisan migrate
	docker compose run --rm artisan tinker

Atalhos com Makefile (opcional)
- make init
- make up / make down / make restart
- make logs / make ps
- make artisan cmd="migrate"
- make composer args="require vendor/pacote"

Reinicializar do zero (opcional)
- Para apagar o conteúdo atual de `backend/` e reinstalar o Laravel 8 devidamente compatível com PHP 8.0:
	FORCE_INIT=1 ./bin/init.sh

Variáveis de ambiente opcionais (arquivo `.env` na raiz para Docker Compose)
- HTTP_PORT=8080
- DB_DATABASE=backend
- DB_USERNAME=backend
- DB_PASSWORD=secret
- DB_PORT=5432
- REDIS_PORT=6379
- PUID=1000
- PGID=1000

Permissões
- Se precisar reaplicar permissões de escrita em `storage` e `bootstrap/cache`:
  ./bin/perm.sh

Observações
- O `.env` interno do Laravel já aponta para `db` (Postgres) e `redis` (Redis) dentro da rede do Docker.
- Para instalar pacotes Composer:
  docker compose run --rm composer require vendor/pacote

Solução de problemas
- Erro ao criar usuário/grupo no build (PUID/PGID vazios): o Dockerfile já tem defaults e não falha sem variáveis. Se precisar rebuildar:
	docker compose build app artisan
- Aviso "the attribute `version` is obsolete" no docker-compose: removido do arquivo; pode ignorar se aparecer em versões antigas do compose.
- Composer exigindo PHP >= 8.2 no `artisan`: o script `init.sh` já fixa `platform.php` para 8.0.30 e roda `composer update` automaticamente. Em projetos existentes, você pode fazer manualmente:
	docker compose run --rm composer config platform.php 8.0.30
	docker compose run --rm composer update --no-interaction
