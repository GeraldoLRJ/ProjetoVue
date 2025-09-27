# Projeto Dockerizado: Laravel 8 + Postgres + Redis

@Bonus (Docker)

Este repositório contém a infraestrutura Docker para subir um projeto Laravel 8 + Vue 2.

Componentes
- PHP-FPM 8.0 (app)
- Nginx (web)
- Postgres 14 (db)
- Redis 7 (redis) (reservado para cache / futura fila)
- Composer (container utilitário)
- Artisan (container utilitário)
- Worker (processamento contínuo de filas Laravel)
- Scheduler (executa o Laravel Scheduler a cada 60s)
- Frontend (Vue)

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

	Isso já inicia também:
	- `worker`: executando `php artisan queue:work --queue=emails,default --tries=3 --sleep=2 --verbose`
	- `scheduler`: loop chamando `php artisan schedule:run` a cada 60s (reservado para futuras tasks; hoje a fila não depende mais dele)

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

6) (Opcional) Ver logs de fila em tempo real:
	docker compose logs -f worker

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

Frontend (Vue 2) no Docker

- Serviço: `frontend` (Node 18 + vue-cli-service)
- Porta: http://localhost:${FRONTEND_PORT:-8081}
- API base (frontend): `VUE_APP_API_BASE=http://localhost:${HTTP_PORT:-8080}/api`

Primeiro acesso:

- Frontend: http://localhost:8081
- Backend API: http://localhost:8080/api

@Bonus (Criação de Usuário)

Login master e primeira empresa (seed):

- Primeira empresa : Master Company

- E-mail: master@local.test
- Senha: master123

Recarga ao vivo:

- O volume anônimo `/app/node_modules` evita que o bind mount sobrescreva as dependências. Mudanças em `frontend/` refletem no container (CHOKIDAR_USEPOLLING=true).

Problemas comuns:

- `vue-cli-service: not found`: normalmente acontece quando `node_modules` foi mascarado pelo bind. O compose já mapeia `/app/node_modules`. Rode `docker compose up -d --build frontend` para corrigir.

APIs principais (backend)

- Nota: O sistema utiliza uma hierarquia de papéis — master (acesso global), admin (acesso total ao próprio tenant) e user (restrito às suas próprias tarefas). O usuário master inicial é criado automaticamente na inicialização do ambiente. Essa hierarquia garante isolamento multi-tenant e controle de privilégios.

- Auth (JWT):
	- POST /api/register
	- POST /api/login
	- POST /api/logout (auth)
	- POST /api/refresh (auth)
	- GET /api/me (auth)

- Tasks (auth): CRUD multi-tenant (usuários veem tarefas do seu tenant; admin pode ver de todos os usuários do tenant (exceto do master); user pode ver apenas as atribpuidas ao seu usário; master vê todas)
	- GET/POST /api/tasks
	- GET/PUT/DELETE /api/tasks/{id}

- Companies (auth + role: master): CRUD de empresas
	- GET/POST /api/companies
	- GET/PUT/DELETE /api/companies/{id}

- Users (auth): gestão de usuários por papel
	- GET /api/users — master: lista todos; admin: apenas usuários do próprio tenant (exceto master)
	- POST /api/users — master: cria em qualquer tenant (tenant_id obrigatório); admin: cria apenas no próprio tenant (não pode criar master)
	- GET /api/users/{id} — master: qualquer; admin: apenas do próprio tenant e não-master
	- PUT /api/users/{id}
		* master: pode alterar nome, email, senha e role de admin/user. Não altera `role` de um master (nem própria) e não altera `tenant_id`.
		* admin: não pode promover a master, nem alterar master, nem trocar tenant.
	- DELETE /api/users/{id} — master: qualquer não-master; admin: apenas do próprio tenant e não-master

Notas:
- A unicidade de e-mail é por tenant: (tenant_id, email) é único.
- O papel master é global e não pode ser criado/alterado por admins.
- No arquivo backend/config/cors.php, está definido para o frontend ser reconhecido apenas na porta 8081 (para garantir que haja apenas um frontend rodando). Qualquer tentativa de chamar a API de outra origem será bloqueada pelo navegador.
	'allowed_origins' => [
        'http://localhost:8081',
        'http://127.0.0.1:8081',
    ],

@Bonus (Filas)

## Fila de E-mails (Notificação de Nova Task)

Fluxo implementado:
1. Ao criar uma Task (`TaskObserver`), é disparado um Job `SendTaskCreatedEmail`.
2. O Job é colocado na fila `emails` com atraso (delay) de 1 minuto.
3. O container `worker` roda continuamente o `queue:work` e processa quando `available_at <= agora`.
4. O Job envia e-mail via Mailtrap usando a `Mailable` `NewTaskCreated` e registra logs em `storage/logs/laravel.log`.
5. Retentativas: até 3 tentativas (`$tries = 3`) com backoff de 60s.
6. Falhas são registradas em `failed_jobs` (job implementa método `failed`).

Arquivo chave:
- `app/Observers/TaskObserver.php`
- `app/Jobs/SendTaskCreatedEmail.php`
- `app/Mail/NewTaskCreated.php`
- View: `resources/views/emails/tasks/created.blade.php`

Alterar atraso (delay):
- Atualmente: `delay(now()->addMinute())` no observer.
- Se quiser alterar para outro período, como 5 minutos: `->delay(now()->addMinutes(5))`.

Verificando fila (Postgres):
```bash
docker compose exec db psql -U ${DB_USERNAME:-backend} -d ${DB_DATABASE:-backend} -c "SELECT id,queue,attempts,available_at,created_at FROM jobs ORDER BY id DESC LIMIT 10;"
```

Logs do Job:
```bash
docker compose logs --since=5m worker | grep SendTaskCreatedEmail
docker compose exec app tail -n 200 storage/logs/laravel.log | grep SendTaskCreatedEmail
```

Reprocessar manualmente (se necessário):
```bash
docker compose exec app php artisan queue:retry all
```

## Configuração de E-mail (Mailtrap)

Exemplo de variáveis no `backend/.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_user
MAIL_PASSWORD=seu_pass
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@local.test"
MAIL_FROM_NAME="Projeto"

QUEUE_CONNECTION=database
```

## Banco de Dados da Fila

- Driver atual: database (`jobs` e `failed_jobs`).
- Índices adicionados (migration `2025_09_26_010000_add_indexes_to_jobs_table`):
	* (`queue`, `reserved_at`)
	* (`available_at`)
- Tabela `failed_jobs` recriada condicionalmente para evitar falhas se removida.

Migrar caso ainda não aplicado:
```bash
docker compose exec app php artisan migrate --force
```

Switch para Redis (opcional futura melhoria):
1. Ajustar `.env`: `QUEUE_CONNECTION=redis`.
2. Garantir serviço `redis` ativo (já existe).
3. (Opcional) Instalar Horizon para painel: `composer require laravel/horizon` e criar serviço no compose rodando `php artisan horizon`.

## Logs e Troubleshooting de Fila

Ver status rápido:
```bash
docker compose logs --tail=50 worker
```

Jobs presos (ver se `available_at` está no futuro):
```bash
docker compose exec db psql -U ${DB_USERNAME:-backend} -d ${DB_DATABASE:-backend} -c "SELECT id,queue,attempts,available_at,EXTRACT(EPOCH FROM NOW()) AS now_epoch FROM jobs ORDER BY id;"
```

Forçar execução imediata (remover delay provisoriamente): alterar o observer e recriar task.

Solução de problemas
- Erro ao criar usuário/grupo no build (PUID/PGID vazios): o Dockerfile já tem defaults e não falha sem variáveis. Se precisar rebuildar:
	docker compose build app artisan
- Aviso "the attribute `version` is obsolete" no docker-compose: removido do arquivo; pode ignorar se aparecer em versões antigas do compose.
- Composer exigindo PHP >= 8.2 no `artisan`: o script `init.sh` já fixa `platform.php` para 8.0.30 e roda `composer update` automaticamente. Em projetos existentes, você pode fazer manualmente:
	docker compose run --rm composer config platform.php 8.0.30
	docker compose run --rm composer update --no-interaction
