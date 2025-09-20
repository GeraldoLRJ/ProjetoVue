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
	- Este passo também gera automaticamente a APP_KEY e o JWT_SECRET no `backend/.env`.

 2a) (Opcional) Rodar migrações/seeders automaticamente pelo init:

	# Apenas migrações
	RUN_DB_MIGRATIONS=1 ./bin/init.sh

	# Migrações + seeders
	RUN_DB_MIGRATIONS=1 RUN_DB_SEEDERS=1 ./bin/init.sh

3) Subir os serviços:
	docker compose up -d --build

 3a) (Se não usou o passo 2a) Rodar as migrações/seeders manualmente:

	# Forma 1 (recomendada): run cria um container efêmero do artisan
	# Observação: o banco (db) precisa estar no ar. Se necessário: `docker compose up -d db`
	docker compose run --rm artisan migrate
	# Opcional: seeders
	docker compose run --rm artisan db:seed

	# Forma 2 (exec): requer o container da app rodando
	# Primeiro suba o app: `docker compose up -d app`
	docker compose exec app php artisan migrate
	docker compose exec app php artisan db:seed

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
- O `./bin/init.sh` gera automaticamente a APP_KEY e o JWT_SECRET. O arquivo `backend/.env` está no `.gitignore` e não deve ser versionado.
- Para instalar pacotes Composer:
  docker compose run --rm composer require vendor/pacote

Solução de problemas
- Erro ao criar usuário/grupo no build (PUID/PGID vazios): o Dockerfile já tem defaults e não falha sem variáveis. Se precisar rebuildar:
	docker compose build app artisan
- Aviso "the attribute `version` is obsolete" no docker-compose: removido do arquivo; pode ignorar se aparecer em versões antigas do compose.
- Composer exigindo PHP >= 8.2 no `artisan`: o script `init.sh` já fixa `platform.php` para 8.0.30 e roda `composer update` automaticamente. Em projetos existentes, você pode fazer manualmente:
	docker compose run --rm composer config platform.php 8.0.30
	docker compose run --rm composer update --no-interaction
