#!/usr/bin/env bash
# init.sh (ESSENCIAL) - Configura o backend Laravel já versionado.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
COMPOSE_FILE="$ROOT_DIR/docker-compose.yml"

echo "==> Verificando acesso ao Docker"
if ! docker info >/dev/null 2>&1; then
  echo "Erro: Docker inacessível (adicione-se ao grupo docker e relogue)." >&2
  exit 1
fi

echo "==> Garantindo estrutura básica"
mkdir -p \
  "$BACKEND_DIR/storage/framework/cache" \
  "$BACKEND_DIR/storage/framework/sessions" \
  "$BACKEND_DIR/storage/framework/views" \
  "$BACKEND_DIR/storage/framework/testing" \
  "$BACKEND_DIR/storage/logs" \
  "$BACKEND_DIR/bootstrap/cache"
touch "$BACKEND_DIR/storage/logs/laravel.log" || true

echo "==> .env"
if [ ! -f "$BACKEND_DIR/.env" ]; then
  cp "$ROOT_DIR/docker/laravel/env.laravel" "$BACKEND_DIR/.env"
  echo "Criado a partir do template."
else
  echo ".env existente."
fi

echo "==> Dependências Composer"
if [ ! -f "$BACKEND_DIR/composer.json" ]; then
  echo "composer.json ausente. (Projeto já deveria ter o backend versionado)." >&2
  exit 1
fi
if [ ! -d "$BACKEND_DIR/vendor" ]; then
  docker compose -f "$COMPOSE_FILE" run --rm composer install --no-interaction
  docker compose -f "$COMPOSE_FILE" run --rm composer config platform.php 8.0.30 || true
else
  echo "vendor já presente."
fi

echo "==> Ajustando permissões (storage & cache)"
chmod -R ug+rwX "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" 2>/dev/null || true
find "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" -type d -exec chmod 775 {} + 2>/dev/null || true
# Se rodado com sudo, tenta devolver ownership
if [ -n "${SUDO_USER:-}" ]; then
  chown -R "$SUDO_USER":"$SUDO_USER" "$BACKEND_DIR" 2>/dev/null || true
fi

artisan() {
  docker compose -f "$COMPOSE_FILE" run --rm artisan "$@"
}

echo "==> storage:link"
[ -L "$BACKEND_DIR/public/storage" ] || artisan storage:link || true

echo "==> APP_KEY"
grep -q '^APP_KEY=base64:' "$BACKEND_DIR/.env" || artisan key:generate || true

echo "==> JWT_SECRET"
grep -q '^JWT_SECRET=' "$BACKEND_DIR/.env" || artisan jwt:secret --force || true

if [ "${RUN_DB_MIGRATIONS:-0}" = "1" ]; then
  echo "==> Subindo db para migrações"
  docker compose -f "$COMPOSE_FILE" up -d db
  echo "==> Migrando"
  artisan migrate --force || true
  if [ "${RUN_DB_SEEDERS:-0}" = "1" ]; then
    echo "==> Seeders"
    artisan db:seed --force || true
  fi
fi

echo "==> Fim. (Pronto para: docker compose up -d)"
