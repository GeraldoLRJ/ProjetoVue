#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"

# Cria projeto Laravel 8 em ./backend usando o contêiner composer
if [ "${FORCE_INIT:-0}" = "1" ]; then
  echo "FORCE_INIT=1 detectado. Removendo conteúdo de ./backend e reinstalando Laravel 8..."
  rm -rf "$BACKEND_DIR"/* "$BACKEND_DIR"/.[!.]* "$BACKEND_DIR"/..?* 2>/dev/null || true
fi

if [ -d "$BACKEND_DIR/vendor" ] && [ "${FORCE_INIT:-0}" != "1" ]; then
  echo "Laravel já inicializado em ./backend. Pulando criação. (Use FORCE_INIT=1 para reinstalar)"
else
  echo "Baixando Laravel 8 em ./backend..."
  mkdir -p "$BACKEND_DIR"
  docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm composer create-project --prefer-dist laravel/laravel:^8.0 .
  echo "Fixando plataforma do Composer para PHP 8.0.30 e atualizando dependências..."
  docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm composer config platform.php 8.0.30
  docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm composer update --no-interaction
fi

# Copia o template .env do Laravel personalizado para Docker
if [ -f "$ROOT_DIR/backend/.env" ]; then
  echo ".env já existe. Mantendo arquivo atual."
else
  echo "Criando .env para Docker..."
  cp "$ROOT_DIR/docker/laravel/env.laravel" "$ROOT_DIR/backend/.env"
fi

# Ajusta permissões e proprietário para storage e bootstrap/cache
echo "Ajustando permissões e proprietário..."
chmod -R ug+rwX "$ROOT_DIR/backend/storage" "$ROOT_DIR/backend/bootstrap/cache" || true
find "$ROOT_DIR/backend/storage" "$ROOT_DIR/backend/bootstrap/cache" -type d -exec chmod 775 {} + || true
chown -R $(id -u):$(id -g) "$ROOT_DIR/backend" || true

echo "Criando link de storage -> public/storage (se necessário)..."
docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan storage:link || true

echo "Gerando key do app..."
docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan key:generate

echo "Gerando JWT_SECRET (jwt:secret)..."
docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan jwt:secret --force || true

# Opcionalmente execute migrações e seeders
if [ "${RUN_DB_MIGRATIONS:-0}" = "1" ]; then
  echo "Subindo serviços de banco (db, redis) para migrações..."
  docker compose -f "$ROOT_DIR/docker-compose.yml" up -d db redis
  echo "Executando migrações..."
  docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan migrate --force || true
  if [ "${RUN_DB_SEEDERS:-0}" = "1" ]; then
    echo "Executando seeders..."
    docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan db:seed --force || true
  fi
fi

echo "Init concluído."
