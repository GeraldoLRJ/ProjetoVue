#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"

# Criação / detecção do projeto Laravel 8 em ./backend
if [ "${FORCE_INIT:-0}" = "1" ]; then
  echo "FORCE_INIT=1 detectado. Removendo conteúdo de ./backend e reinstalando Laravel 8..."
  rm -rf "$BACKEND_DIR"/* "$BACKEND_DIR"/.[!.]* "$BACKEND_DIR"/..?* 2>/dev/null || true
fi

mkdir -p "$BACKEND_DIR"

ARTISAN_FILE="$BACKEND_DIR/artisan"
COMPOSER_JSON="$BACKEND_DIR/composer.json"

if [ -f "$ARTISAN_FILE" ] || [ -f "$COMPOSER_JSON" ]; then
  if [ "${FORCE_INIT:-0}" = "1" ]; then
    echo "Reinstalando projeto (FORCE_INIT=1)..."
    docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm composer create-project --prefer-dist laravel/laravel:^8.0 .
  else
    echo "Projeto Laravel já presente em ./backend (artisan ou composer.json encontrado)."
    if [ ! -d "$BACKEND_DIR/vendor" ]; then
      echo "Diretório vendor ausente. Executando composer install..."
      echo "Garantindo diretórios de cache do Laravel..."
      mkdir -p \
        "$BACKEND_DIR/storage/framework" \
        "$BACKEND_DIR/storage/framework/cache" \
        "$BACKEND_DIR/storage/framework/sessions" \
        "$BACKEND_DIR/storage/framework/views" \
        "$BACKEND_DIR/storage/framework/testing" \
        "$BACKEND_DIR/bootstrap/cache"
      chmod -R ug+rwX "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" || true
      docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm composer install --no-interaction
      docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm composer config platform.php 8.0.30 || true
    else
      echo "Dependências já instaladas (vendor existe). Pulando composer install."
    fi
  fi
else
  # Projeto não existe: criar via create-project
  echo "Baixando Laravel 8 em ./backend (novo projeto)..."
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

# Ajusta permissões e proprietário para storage e bootstrap/cache (garante antes de artisan)
echo "Ajustando permissões e proprietário..."
chmod -R ug+rwX "$ROOT_DIR/backend/storage" "$ROOT_DIR/backend/bootstrap/cache" || true
find "$ROOT_DIR/backend/storage" "$ROOT_DIR/backend/bootstrap/cache" -type d -exec chmod 775 {} + || true
chown -R $(id -u):$(id -g) "$ROOT_DIR/backend" || true

if [ -d "$BACKEND_DIR/vendor" ]; then
  echo "Executando passos artisan (vendor presente)..."
  echo "Criando link de storage -> public/storage (se necessário)..."
  docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan storage:link || true

  echo "Gerando key do app..."
  docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan key:generate || true

  echo "Gerando JWT_SECRET (jwt:secret)..."
  docker compose -f "$ROOT_DIR/docker-compose.yml" run --rm artisan jwt:secret --force || true
else
  echo "Dependências não instaladas (vendor ausente). Pulando comandos artisan agora. Rode novamente após install se necessário."
fi

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
