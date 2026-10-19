#!/bin/bash

# Deploy LOCAL do ambiente de DEV (http://192.168.15.10:8084).
# Roda no próprio servidor, a partir de /opt/containers/spigo-portal-dev: não usa SSH nem rsync.
# Uso:
#   ./deploy-local.sh         deploy completo: rebuild da imagem, restart e migrate
#   ./deploy-local.sh --fast  copia só os *.php para o container em execução, restart e migrate (sem rebuild)

set -e

CONTAINER="spigo-portal-dev-app"
CONTAINER_PATH="/var/www/html"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

dc() { docker compose --env-file .env.compose "$@"; }

FAST=0
[ "${1:-}" = "--fast" ] && FAST=1

if [ "$FAST" -eq 1 ]; then
    echo "=== DEPLOY LOCAL (fast) - SPIGO PORTAL DEV ==="

    echo "[1/3] Copiando arquivos PHP para o container..."
    if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
        echo "ERRO: container $CONTAINER não está em execução (rode sem --fast)."
        exit 1
    fi
    find . \( -path ./vendor -o -path ./node_modules -o -path ./storage -o -path ./.git \) -prune -o \
        \( -name '*.php' -o -path './app/Modules/*/resources/templates/*.xlsx' \) -type f -print0 \
        | tar --null -T - -cf - | docker cp - "$CONTAINER:$CONTAINER_PATH/"

    echo "[2/3] Reiniciando container (flush opcache)..."
    dc restart "$CONTAINER"
else
    echo "=== DEPLOY LOCAL - SPIGO PORTAL DEV ==="

    echo "[1/3] Build e restart..."
    mkdir -p storage/app/public
    rm -f public/hot
    dc up -d --build

    echo "[2/3] Aguardando container..."
fi

echo "[3/3] Migrations e permissões..."
until dc exec -T "$CONTAINER" php artisan migrate --force; do echo "     aguardando banco..."; sleep 3; done
dc exec -T -u root "$CONTAINER" sh -c 'chown -R www-data:www-data /var/www/html/storage && chmod -R 775 /var/www/html/storage && ln -sfn ../storage/app/public public/storage'
dc exec -T "$CONTAINER" php artisan optimize:clear || true

echo ""
echo "=== DEPLOY LOCAL CONCLUÍDO: http://192.168.15.10:8084 ==="
