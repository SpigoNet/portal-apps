#!/bin/bash

# Deploy do ambiente de DEV (http://192.168.15.10:8084), no mesmo servidor da produção.
# Mesmo fluxo do deploy.sh, mas em /opt/containers/spigo-portal-dev, com containers/banco/volumes próprios.
# Primeira execução: cria .env (APP_KEY gerada) e .env.compose (senha do banco) no servidor.

set -e

REMOTE_HOST="apps.spigo.net"
REMOTE_USER="gustavo"
REMOTE_BASE="/opt/containers/spigo-portal-dev"
CONTAINER="spigo-portal-dev-app"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SSH_OPTS="-o StrictHostKeyChecking=no -o ConnectTimeout=10"

RSYNC_EXCLUDES=(
    --exclude '.git' --exclude 'vendor' --exclude 'node_modules'
    --exclude '.env' --exclude '.env.compose' --exclude '.env.example'
    --exclude 'storage/app/public' --exclude 'storage/logs' --exclude 'public/hot'
    --exclude '.opencode' --exclude '.vscode' --exclude 'tests' --exclude '.github'
    --exclude 'ComfyQueue_Worker.ipynb' --exclude 'phpunit.xml' --exclude '.phpunit.result.cache'
    --exclude 'README.md' --exclude 'claude-gateway' --exclude 'codex-gateway'
    --exclude 'nohup.out' --exclude 'rc.log' --exclude 'deploy-local.sh'
    --exclude 'docker-compose.yml' --exclude 'docker-compose.dev.yml'
)

remote_cmd() { ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "$1"; }
dc() { remote_cmd "cd $REMOTE_BASE && docker compose --env-file .env.compose $1"; }

echo "=== DEPLOY DEV - SPIGO PORTAL ($REMOTE_HOST:$REMOTE_BASE) ==="

echo "[1/5] Preparando diretório..."
remote_cmd "mkdir -p $REMOTE_BASE/storage/app/public"

echo "[2/5] Sincronizando código..."
rsync -az --delete "${RSYNC_EXCLUDES[@]}" -e "ssh $SSH_OPTS" "$SCRIPT_DIR/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_BASE/"
rsync -az -e "ssh $SSH_OPTS" "$SCRIPT_DIR/docker-compose.dev.yml" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_BASE/docker-compose.yml"

echo "[3/5] Verificando .env e .env.compose..."
remote_cmd "cd $REMOTE_BASE && [ -f .env.compose ] || { echo DEV_DB_PASSWORD=\$(head -c 24 /dev/urandom | base64 | tr -dc A-Za-z0-9) > .env.compose; chmod 600 .env.compose; echo '     .env.compose criado'; }"
if ! remote_cmd "[ -f $REMOTE_BASE/.env ]"; then
    rsync -az -e "ssh $SSH_OPTS" "$SCRIPT_DIR/.env.dev.example" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_BASE/.env"
    # A chave é gerada aqui no host: o .env é um bind mount e o container não consegue gravar nele
    remote_cmd "sed -i \"s|^APP_KEY=.*|APP_KEY=base64:\$(openssl rand -base64 32)|\" $REMOTE_BASE/.env"
    echo "     .env criado a partir de .env.dev.example (com APP_KEY nova)"
fi

echo "[4/5] Build e restart..."
remote_cmd "rm -f $REMOTE_BASE/public/hot"
dc "up -d --build"

echo "[5/5] Migrations e permissões..."
for i in $(seq 1 20); do dc "exec -T $CONTAINER php artisan db:show --counts" >/dev/null 2>&1 && break; echo "     aguardando banco..."; sleep 3; done
dc "exec -T $CONTAINER php artisan migrate --force"
dc "exec -T -u root $CONTAINER sh -c 'chown -R www-data:www-data /var/www/html/storage && chmod -R 775 /var/www/html/storage && ln -sfn ../storage/app/public public/storage'"
dc "exec -T $CONTAINER php artisan optimize:clear" || true

echo ""
echo "=== DEPLOY DEV CONCLUÍDO: http://192.168.15.10:8084 ==="
