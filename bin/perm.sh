#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

echo "Aplicando permissões em storage e bootstrap/cache..."
chmod -R ug+rwX "$ROOT_DIR/backend/storage" "$ROOT_DIR/backend/bootstrap/cache" || true
find "$ROOT_DIR/backend/storage" "$ROOT_DIR/backend/bootstrap/cache" -type d -exec chmod 775 {} + || true
echo "Feito."
