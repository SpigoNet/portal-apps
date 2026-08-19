#!/bin/bash

set -e

# === CONFIGURAÇÃO ===
REMOTE_HOST="apps.spigo.net"
REMOTE_USER="gustavo"
REMOTE_BASE="/opt/containers/spigo-portal"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_DIR="$SCRIPT_DIR"   # O repo é o contexto de build (contém Dockerfile, composer.json, etc.)

# Pastas/arquivos que NÃO devem ser enviados (reconstruídos no build ou persistidos no servidor)
RSYNC_EXCLUDES=(
    --exclude '.git'
    --exclude 'vendor'
    --exclude 'node_modules'
    --exclude '.env'
    --exclude '.env.example'
    --exclude 'storage/app/public'
    --exclude 'public/hot'
    --exclude '.opencode'
    --exclude '.vscode'
    --exclude 'tests'
    --exclude '.github'
    --exclude 'ComfyQueue_Worker.ipynb'
    --exclude 'phpunit.xml'
    --exclude '.phpunit.result.cache'
    --exclude 'README.md'
)

SSH_OPTS="-o StrictHostKeyChecking=no -o ConnectTimeout=10"

echo "=========================================="
echo "  DEPLOY - SPIGO PORTAL (build local)"
echo "=========================================="
echo "Servidor: $REMOTE_USER@$REMOTE_HOST"
echo "Destino:  $REMOTE_BASE"
echo ""

# Função para executar comando remoto
remote_cmd() {
    ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "$1"
}

# 1. Testar conexão
echo "[1/5] Testando conexão SSH..."
if ! remote_cmd "echo 'OK'" > /dev/null 2>&1; then
    echo "ERRO: Não foi possível conectar ao servidor $REMOTE_HOST"
    exit 1
fi
echo "     Conexão OK!"

# 2. Criar diretório base e backup da versão anterior
echo "[2/5] Preparando diretório e backup..."
remote_cmd "mkdir -p $REMOTE_BASE"
remote_cmd "rm -rf $REMOTE_BASE.bak && ([ -f $REMOTE_BASE/Dockerfile ] && cp -a $REMOTE_BASE $REMOTE_BASE.bak || true)"
echo "     Diretório pronto!"

# 3. Sincronizar projeto (código fonte) com rsync
echo "[3/5] Sincronizando código (rsync)..."
rsync -az --delete "${RSYNC_EXCLUDES[@]}" \
    -e "ssh $SSH_OPTS" \
    "$REPO_DIR/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_BASE/"
echo "     Código sincronizado!"

# 4. Validar .env no servidor
echo "[4/5] Verificando .env no servidor..."
if ! remote_cmd "[ -f $REMOTE_BASE/.env ]"; then
    echo "AVISO: .env não existe no servidor. Criando a partir do .env.example..."
    remote_cmd "cp $REMOTE_BASE/.env.example $REMOTE_BASE/.env"
    echo "       Edite $REMOTE_BASE/.env no servidor antes de continuar, se necessário."
fi

# 5. Build + restart
echo "[5/5] Buildando e reiniciando containers..."
# Garante que nenhum public/hot (servidor de dev) do destino entre no contexto de build
remote_cmd "rm -f $REMOTE_BASE/public/hot"
remote_cmd "cd $REMOTE_BASE && docker compose down && docker compose up -d --build"

# Ajusta permissões do storage montado (bind mount criado como root no host;
# o Apache roda como www-data e precisa de escrita para os uploads)
echo "     Ajustando permissões de storage (uploads)..."
remote_cmd "cd $REMOTE_BASE && docker compose exec -T -u root spigo-portal-app sh -c 'mkdir -p /var/www/html/storage/app/public && chown -R www-data:www-data /var/www/html/storage/app/public && chmod -R 775 /var/www/html/storage/app/public'"

# Garante o symlink public/storage -> storage/app/public (servir downloads via /storage/...)
remote_cmd "cd $REMOTE_BASE && docker compose exec -T spigo-portal-app ln -sfn ../storage/app/public public/storage"

remote_cmd "rm -rf $REMOTE_BASE.bak"
echo "     Deploy concluído!"

echo ""
echo "=========================================="
echo "  DEPLOY CONCLUÍDO!"
echo "  Acesse: https://apps.spigo.net"
echo "=========================================="
