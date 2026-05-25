# Diagnóstico e Resolução: Erro SFTP no Módulo ANT

## Problema
**Erro:** Falha de autenticação SSH ao tentar entregar trabalho
```
phpseclib3\Net\SSH2->login('cdnuser', 'GuMa1726')
```

## Causa Raiz
O módulo ANT tenta fazer upload de arquivos via SFTP para `files.spigo.net:2222`, mas a autenticação está falhando com as credenciais fornecidas.

## Contexto da Infraestrutura

**Topologia:**
- **Laravel:** Servidor externo `/home2/spigo594/apps/` (tenta conectar remotamente)
- **SFTP:** Container Docker em rede local (SUA REDE)
- **Acesso:** Redirecionamento de porta (ex: porta 2222 → container interno)
- **Hostname Público:** `files.spigo.net` (aponta para seu IP público com redirecionamento)
- **Credenciais:** `cdnuser` / `GuMa1726`
- **Porta:** `2222` (exposta para internet)
- **Storage Path:** `/data/uploads`

**Fluxo de Conexão:**
```
Laravel (servidor externo)
    ↓
files.spigo.net:2222  (DNS público)
    ↓
IP Público + Redirecionamento Porta 2222
    ↓
Container SFTP (rede local)
```

---

## Checklist de Diagnóstico

### 1. Verificar Conectividade SFTP (Local)
Execute o comando de teste no servidor (`/home2/spigo594/apps/`):
```bash
php artisan sftp:test
```

Este comando irá:
- ✓ Testar conexão SSH básica
- ✓ Verificar se raiz SFTP é acessível
- ✓ Listar conteúdo do servidor
- ✓ Exibir informações de diagnóstico

**Copie o output completo** para diagnóstico.

### 2. Verificar Credenciais em `.env`
Confirme que as credenciais estão corretas em `/home/gustavo/Codes/portal-apps/.env`:

```
SFTP_HOST=files.spigo.net
SFTP_PORT=2222
SFTP_USERNAME=cdnuser
SFTP_PASSWORD=GuMa1726
SFTP_ROOT=/data/uploads
CDN_URL=https://files.spigo.net
```

**Ações:**
- [ ] Confirmar se `files.spigo.net` é o host correto
- [ ] Confirmar se porta `2222` está abierta
- [ ] Confirmar se credenciais `cdnuser` / `GuMa1726` são válidas
- [ ] Verificar com administrador do servidor se credenciais expiraram

### 3. Testar Conectividade de Rede

Teste acesso ao host **do servidor `/home2/spigo594/`**:

#### 2.1 Resolver DNS
```bash
# Qual IP o DNS retorna para files.spigo.net?
nslookup files.spigo.net
# ou
dig files.spigo.net

# Esperado: Um IP que direcione para o container Docker
```

#### 2.2 Testar Porta (Conectividade)
```bash
# Teste se porta 2222 está respondendo
telnet files.spigo.net 2222
# ou
nc -zv files.spigo.net 2222

# Esperado: Conexão bem-sucedida (não erro de "Connection refused")
```

#### 2.3 Testar SSH Direto
```bash
# Teste login SSH com as credenciais
ssh -p 2222 cdnuser@files.spigo.net

# Será solicitada senha: GuMa1726
# Esperado: Conexão bem-sucedida
```

**Se algum teste falhar:**
- ✗ `nslookup` falha → DNS não está resolvendo corretamente
- ✗ `telnet` falha → Porta bloqueada por firewall ou host offline
- ✗ `ssh` falha → Credenciais incorretas ou permissões do usuário

### 4. Verificar Permissões de Usuário

O usuário `cdnuser` no servidor SFTP deve ter:
- [ ] Permissão de leitura/escrita em `/data/uploads`
- [ ] Permissão de criar diretórios
- [ ] Acesso via autenticação por senha (não apenas chave SSH)

### 5. Verificar Container Docker (No servidor com Docker)

Se tem acesso ao servidor com Docker:

#### 5.1 Verificar se container está rodando
```bash
docker ps | grep cdn_uploader

# Esperado: Container listado e com status "Up"
```

#### 5.2 Verificar logs do container
```bash
docker logs cdn_uploader

# Procure por erros de inicialização ou autenticação
```

#### 5.3 Testar conectividade DENTRO da rede Docker
```bash
# Se outro container está na rede 'proxy', teste:
docker exec <outro-container> nc -zv sftp 2222

# Esperado: Conexão bem-sucedida
```

#### 5.4 Verificar porta exposta
```bash
docker port cdn_uploader

# Esperado: 2222/tcp -> 0.0.0.0:2222
```

### 6. Resoluções Possíveis

#### Opção A: DNS Não está Resolvendo
Se `nslookup files.spigo.net` retorna erro:

```bash
# Verificar /etc/hosts
cat /etc/hosts

# Se necessário, adicionar manualmente:
# echo "127.0.0.1 files.spigo.net" >> /etc/hosts
# ou usar IP do servidor Docker:
# echo "192.168.x.x files.spigo.net" >> /etc/hosts
```

**Solução:** Atualize DNS ou adicione entrada em `/etc/hosts` do servidor Laravel.

---

#### Opção B: Porta 2222 Bloqueada por Firewall
Se `telnet files.spigo.net 2222` falha com "Connection refused":

```bash
# Verificar firewall no servidor Docker
sudo ufw status
sudo ufw allow 2222

# Ou verificar iptables
sudo iptables -L | grep 2222

# Ou reiniciar container para reexposição de porta
docker restart cdn_uploader
```

**Solução:** Liberar porta 2222 no firewall.

---

#### Opção C: Container Docker Não Está Respondendo
Se o container está offline ou com problemas:

```bash
# Reiniciar container
docker restart cdn_uploader

# Ou reconstruir
docker-compose up -d sftp

# Verificar logs
docker logs -f cdn_uploader
```

**Solução:** Reiniciar container ou verificar logs.

---

#### Opção D: Host Externo Errado
Se `files.spigo.net` não aponta para container correto:

Verificar qual IP está sendo resolvido:
```bash
# Do servidor Laravel
nslookup files.spigo.net
# ou
getent hosts files.spigo.net
```

Se IP está errado, atualizar:
- DNS do domínio
- Ou arquivo `/etc/hosts`

**Solução:** Alinhar DNS para apontar ao servidor Docker correto.

---

#### Opção E: Credenciais Incorretas em Produção
Se SSH conecta mas falha autenticação:

1. Verificar credenciais no `.env` em produção:
   ```
   SFTP_USERNAME=cdnuser
   SFTP_PASSWORD=GuMa1726
   ```

2. Se estão corretas, resetar usuário no container:
   ```bash
   # Recrear container com mesmas credenciais
   docker-compose down sftp
   docker-compose up -d sftp
   ```

**Solução:** Validar credenciais ou resetar container.

## Logs de Erro

Logs estão em `/storage/logs/` . Procure por:
- `SFTP Upload Attempt`
- `SFTP Connection Failed`
- `SFTP Upload Failed`

Exemplo de como executar tail:
```bash
tail -f storage/logs/laravel.log | grep -i sftp
```

## Arquivos Modificados

- `/app/Console/Commands/TestSftpConnection.php` - Novo comando de teste
- `/app/Modules/ANT/Services/TrabalhoUploadService.php` - Novo service com melhor tratamento
- `/app/Modules/ANT/Http/Controllers/TrabalhoController.php` - Melhor tratamento de erro

## Próximos Passos

1. Execute `php artisan sftp:test` para diagnosticar
2. Compartilhe o resultado do teste
3. Verifique credenciais SFTP em produção
4. Atualize `.env` se necessário
5. Teste entrega de trabalho novamente

---

**Último Update:** 25/05/2026
