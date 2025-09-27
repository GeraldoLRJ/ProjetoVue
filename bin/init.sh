#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"

# Detecta usuário real (evita root:root quando roda via sudo)
HOST_USER="${SUDO_USER:-$(id -un)}"
HOST_UID="$(id -u "$HOST_USER")"
HOST_GID="$(id -g "$HOST_USER")"

# Verificação inicial: acesso ao daemon Docker sem sudo
if ! docker info >/dev/null 2>&1; then
  echo "[ERRO] Não foi possível acessar o daemon Docker como o usuário '$HOST_USER'."
  echo "Causa comum: usuário não faz parte do grupo 'docker'."
  echo
  #!/usr/bin/env bash
  # -----------------------------------------------------------------------------
  # init.sh (reinventado)
  # Inicializa/repara o backend Laravel dentro do Docker de forma idempotente.
  # Suporta projeto já versionado (sem need de create-project na maioria dos casos).
  # -----------------------------------------------------------------------------
  set -euo pipefail

  SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
  ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
  BACKEND_DIR="$ROOT_DIR/backend"
  DOCKER_COMPOSE_FILE="$ROOT_DIR/docker-compose.yml"

  # Vars de controle (compat + novas)
  FORCE_INIT="${FORCE_INIT:-0}"            # wipe e recria skeleton (se realmente quiser)
  RUN_DB_MIGRATIONS="${RUN_DB_MIGRATIONS:-0}"
  RUN_DB_SEEDERS="${RUN_DB_SEEDERS:-0}"
  QUIET="${QUIET:-0}"
  PRESERVE_ENV="${PRESERVE_ENV:-1}"          # ao forçar recriação preserva .env (default: sim)

  # Detecta usuário real (evita root:root se rodado via sudo)
  HOST_USER="${SUDO_USER:-$(id -un)}"
  HOST_UID="$(id -u "$HOST_USER")"
  HOST_GID="$(id -g "$HOST_USER")"

  COLOR_RESET="\033[0m"; COLOR_BLUE="\033[34m"; COLOR_GREEN="\033[32m"; COLOR_YELLOW="\033[33m"; COLOR_RED="\033[31m";
  log() { [ "$QUIET" = "1" ] && return 0; printf "%b[init]%b %s\n" "$COLOR_BLUE" "$COLOR_RESET" "$1"; }
  ok()  { [ "$QUIET" = "1" ] && return 0; printf "%b[ok]%b   %s\n" "$COLOR_GREEN" "$COLOR_RESET" "$1"; }
  warn(){ [ "$QUIET" = "1" ] && return 0; printf "%b[warn]%b %s\n" "$COLOR_YELLOW" "$COLOR_RESET" "$1"; }
  err() { printf "%b[erro]%b %s\n" "$COLOR_RED" "$COLOR_RESET" "$1" >&2; }

  abort() { err "$1"; exit 1; }

  require_docker() {
    if ! docker info >/dev/null 2>&1; then
      err "Não foi possível acessar o daemon Docker (usuário: $HOST_USER)."
      echo "Adicione ao grupo docker e relogue:"
      echo "  sudo usermod -aG docker $HOST_USER"
      echo "Depois: docker ps"
      exit 1
    fi
  }

  wipe_backend() {
    [ "$FORCE_INIT" = "1" ] || return 0
    if [ -d "$BACKEND_DIR" ]; then
      log "FORCE_INIT=1: limpando backend/* (preservar .env = $PRESERVE_ENV)";
      local TMP_ENV="";
      if [ "$PRESERVE_ENV" = "1" ] && [ -f "$BACKEND_DIR/.env" ]; then
        TMP_ENV="$(mktemp)"; cp "$BACKEND_DIR/.env" "$TMP_ENV"; fi
      rm -rf "$BACKEND_DIR"/* "$BACKEND_DIR"/.[!.]* "$BACKEND_DIR"/..?* 2>/dev/null || true
      [ -n "$TMP_ENV" ] && { mkdir -p "$BACKEND_DIR"; mv "$TMP_ENV" "$BACKEND_DIR/.env"; }
    fi
  }

  ensure_structure() {
    mkdir -p \
      "$BACKEND_DIR" \
      "$BACKEND_DIR/storage/framework/cache" \
      "$BACKEND_DIR/storage/framework/sessions" \
      "$BACKEND_DIR/storage/framework/views" \
      "$BACKEND_DIR/storage/framework/testing" \
      "$BACKEND_DIR/storage/logs" \
      "$BACKEND_DIR/bootstrap/cache"
    touch "$BACKEND_DIR/storage/logs/laravel.log" || true
    ok "Estrutura de diretórios garantida"
  }

  composer_install_or_update() {
    if [ ! -f "$BACKEND_DIR/composer.json" ]; then
      log "composer.json não encontrado. (Projeto já versionado deveria conter).";
      log "Criando novo skeleton Laravel 8...";
      docker compose -f "$DOCKER_COMPOSE_FILE" run --rm composer create-project --prefer-dist laravel/laravel:^8.0 .
    elif [ ! -d "$BACKEND_DIR/vendor" ]; then
      log "Instalando dependências (composer install)...";
      docker compose -f "$DOCKER_COMPOSE_FILE" run --rm composer install --no-interaction
    else
      ok "Dependências já presentes (vendor)"
    fi
    # Garante plataforma fixa (idempotente)
    docker compose -f "$DOCKER_COMPOSE_FILE" run --rm composer config platform.php 8.0.30 || true
  }

  copy_env_if_missing() {
    if [ -f "$BACKEND_DIR/.env" ]; then
      ok ".env existente (preservado)"
    else
      log "Copiando template .env (docker/laravel/env.laravel)";
      cp "$ROOT_DIR/docker/laravel/env.laravel" "$BACKEND_DIR/.env"
    fi
  }

  fix_permissions() {
    chmod -R ug+rwX "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" 2>/dev/null || true
    find "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" -type d -exec chmod 775 {} + 2>/dev/null || true
    chown -R "$HOST_UID":"$HOST_GID" "$BACKEND_DIR" 2>/dev/null || true
    ok "Permissões ajustadas (owner: $HOST_USER)"
  }

  artisan_safe() {
    # Só executa se vendor existir
    [ -d "$BACKEND_DIR/vendor" ] || { warn "Pulando artisan ($1) - vendor ausente"; return 0; }
    docker compose -f "$DOCKER_COMPOSE_FILE" run --rm artisan "$@" || true
  }

  ensure_app_key() {
    if ! grep -q '^APP_KEY=base64:' "$BACKEND_DIR/.env" 2>/dev/null; then
      log "Gerando APP_KEY"; artisan_safe key:generate; else ok "APP_KEY já definido"; fi
  }

  ensure_jwt_secret() {
    if ! grep -q '^JWT_SECRET=' "$BACKEND_DIR/.env" 2>/dev/null; then
      log "Gerando JWT_SECRET"; artisan_safe jwt:secret --force; else ok "JWT_SECRET já definido"; fi
  }

  storage_link() {
    if [ ! -L "$BACKEND_DIR/public/storage" ]; then
      log "Criando storage:link"; artisan_safe storage:link; else ok "Link storage já existe"; fi
  }

  migrate_and_seed() {
    [ "$RUN_DB_MIGRATIONS" = "1" ] || return 0
    log "Subindo serviço de banco (db) para migrações"; docker compose -f "$DOCKER_COMPOSE_FILE" up -d db
    log "Executando migrate"; artisan_safe migrate --force
    if [ "$RUN_DB_SEEDERS" = "1" ]; then
      log "Executando seeders"; artisan_safe db:seed --force; fi
  }

  summary() {
    [ "$QUIET" = "1" ] && return 0
    echo
    echo "----------------------------------------"
    echo " Init concluído"
    echo "  - FORCE_INIT=$FORCE_INIT"
    echo "  - RUN_DB_MIGRATIONS=$RUN_DB_MIGRATIONS"
    echo "  - RUN_DB_SEEDERS=$RUN_DB_SEEDERS"
    echo "  - PRESERVE_ENV=$PRESERVE_ENV"
    echo "----------------------------------------"
  }

  main() {
    require_docker
    wipe_backend
    ensure_structure
    composer_install_or_update
    copy_env_if_missing
    fix_permissions
    storage_link
    ensure_app_key
    ensure_jwt_secret
    migrate_and_seed
    summary
  }

  main "$@"
