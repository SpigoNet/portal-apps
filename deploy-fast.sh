#!/bin/bash

# Fast deploy: copia APENAS arquivos PHP para o container em execução
# e roda as migrations. Útil para pequenas alterações de código PHP,
# sem rebuild da imagem Docker.

set -e

# === CONFIGURAÇÃO ===
REMOTE_HOST="apps.spigo.net"
REMOTE_USER="gustavo"
REMOTE_BASE="/opt/containers/spigo-portal"
CONTAINER="spigo-portal-app"
CONTAINER_PATH="/var/www/html"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_DIR="$SCRIPT_DIR"

SSH_OPTS="-o StrictHostKeyChecking=no -o ConnectTimeout=10"

echo "=========================================="
echo "  FAST DEPLOY - SPIGO PORTAL (PHP + migrate)"
echo "=========================================="
echo "Servidor: $REMOTE_USER@$REMOTE_HOST"
echo "Container: $CONTAINER"
echo ""

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

# 2. Validar que o container está no ar
echo "[2/5] Verificando container..."
if ! remote_cmd "docker ps --format '{{.Names}}' | grep -qx '$CONTAINER'"; then
    echo "ERRO: Container $CONTAINER não está em execução."
    exit 1
fi
echo "     Container OK!"

# 3. Rsync somente arquivos PHP para um diretório temporário no servidor
echo "[3/5] Copiando apenas arquivos PHP..."
TMP="/tmp/spigo-fast-$(date +%s)"
remote_cmd "mkdir -p $TMP"
rsync -az -e "ssh $SSH_OPTS" \
    --prune-empty-dirs \
    --include='*/' \
    --include='*.php' \
    --exclude='storage' \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='*' \
    "$REPO_DIR/" "$REMOTE_USER@$REMOTE_HOST:$TMP/"
echo "     PHP sincronizado!"

# 4. Copiar para dentro do container em execução (sem rebuild)
echo "[4/5] Aplicando no container..."
remote_cmd "docker cp $TMP/. $CONTAINER:$CONTAINER_PATH/"
remote_cmd "rm -rf $TMP"

# Garante que o storage permaneça gravável (o docker cp não deve sobrescrevê-lo)
remote_cmd "cd $REMOTE_BASE && docker compose exec -T -u root $CONTAINER sh -c 'chown -R www-data:www-data /var/www/html/storage && chmod -R 775 /var/www/html/storage'" || true

# Reinicia o container para limpar o OPcache (senão ele serve o código antigo em memória)
echo "     Reiniciando container (flush opcache)..."
remote_cmd "cd $REMOTE_BASE && docker compose restart $CONTAINER"

# Limpa caches do Laravel para garantir que código novo seja usado
remote_cmd "cd $REMOTE_BASE && docker compose exec -T $CONTAINER php artisan optimize:clear" || true
echo "     Código aplicado!"

# 5. Rodar migrations
echo "[5/5] Rodando migrations..."
remote_cmd "cd $REMOTE_BASE && docker compose exec -T $CONTAINER php artisan migrate --force"
echo "     Migrations concluídas!"

echo ""
echo "=========================================="
echo "  FAST DEPLOY CONCLUÍDO!"
echo "  (sem rebuild de imagem)"
echo "=========================================="
