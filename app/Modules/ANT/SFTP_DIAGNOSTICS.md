# Diagnóstico e Resolução: Erro SFTP no Módulo ANT

## Problema
**Erro:** Falha de autenticação SSH ao tentar entregar trabalho
```
phpseclib3\Net\SSH2->login('cdnuser', 'GuMa1726')
```

## Topologia da Infraestrutura

```
┌──────────────────────────┐
│ Laravel Externo          │
│ /home2/spigo594/apps/    │
│ (Servidor externo)       │
└────────────┬─────────────┘
             │ Tenta conectar
             ↓
    ┌────────────────────┐
    │ files.spigo.net:   │
    │ 2222               │
    │ (DNS Público)      │
    └────────────┬───────┘
                 │ Redirecionamento Porta
                 ↓
    ┌────────────────────────────────────┐
    │ Roteador/Firewall (SUA REDE)       │
    │ Port Forward: 2222 → 2222          │
    └────────────┬───────────────────────┘
                 │
                 ↓
    ┌────────────────────────────────────┐
    │ Container Docker (LOCAL)           │
    │ cdn_uploader                       │
    │ SSH porta 2222                     │
    │ /data/uploads                      │
    └────────────────────────────────────┘
```

**Configuração:**
- **Laravel:** Servidor externo `/home2/spigo594/apps/`
- **SFTP:** Container Docker na sua rede local
- **Acesso:** Redirecionamento de porta 2222
- **Hostname:** `files.spigo.net` (seu DNS público)
- **Credenciais:** `cdnuser` / `GuMa1726`

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

### 3. Testar Conectividade de Rede

**Testes DO SERVIDOR EXTERNO** (onde Laravel roda em `/home2/spigo594/apps/`):

#### 3.1 Resolver DNS
```bash
# Qual IP o DNS retorna para files.spigo.net?
nslookup files.spigo.net
# ou
dig files.spigo.net

# Esperado: Seu IP PÚBLICO que redireciona para o container
```

#### 3.2 Testar Conexão à Porta 2222
```bash
# Teste se consegue conectar em files.spigo.net:2222
telnet files.spigo.net 2222
# ou
nc -zv files.spigo.net 2222

# Esperado: "Connection successful"
# Errado: "Connection refused" = redirecionamento inativo ou firewall
```

#### 3.3 Testar SSH com Credenciais
```bash
ssh -p 2222 cdnuser@files.spigo.net

# Será solicitada senha: GuMa1726
# Esperado: Acesso bem-sucedido
```

**Se algum teste falhar:**
| Teste | Resultado | Causa Provável | Ação |
|-------|-----------|-----------------|------|
| `nslookup` | Erro/não resolve | DNS não está configurado | Configurar DNS ou usar IP público |
| `telnet` | "Connection refused" | Redirecionamento inativo | Verificar port-forwarding no roteador |
| `telnet` | Timeout | Firewall ou host offline | Liberar porta 2222 no firewall |
| `ssh` | "Permission denied" | Credenciais erradas | Validar `cdnuser` / `GuMa1726` |

### 4. Verificar Permissões de Usuário

O usuário `cdnuser` no servidor SFTP deve ter:
- [ ] Permissão de leitura/escrita em `/data/uploads`
- [ ] Permissão de criar diretórios
- [ ] Acesso via autenticação por senha (não apenas chave SSH)

### 5. Verificar Container Docker (Na SUA REDE LOCAL)

#### 5.1 Verificar se container está rodando
```bash
docker ps | grep cdn_uploader

# Esperado: Container listado com status "Up"
```

#### 5.2 Verificar logs do container
```bash
docker logs cdn_uploader

# Procure por erros de inicialização
```

#### 5.3 Verificar porta exposta
```bash
docker port cdn_uploader

# Esperado: 2222/tcp -> 0.0.0.0:2222
```

### 6. Verificar Port-Forwarding (Redirecionamento)

**Na SUA REDE LOCAL:**

#### 6.1 Verificar Roteador
1. Acesse interface do roteador (ex: `192.168.1.1`)
2. Procure por "Port Forwarding" ou "Encaminhamento de Porta"
3. Confirme que existe uma regra:
   - **Porta Externa:** `2222`
   - **Porta Interna:** `2222`
   - **IP Interno:** IP do computador onde container roda
   - **Status:** Ativado

#### 6.2 Testar Port-Forwarding (Localmente)
```bash
# De um outro PC/servidor externo, teste:
telnet <seu-ip-publico> 2222

# Ou se tiver SSH em outro servidor:
ssh -p 2222 cdnuser@<seu-ip-publico>
```

#### 6.3 Descobrir seu IP Público
```bash
# De um terminal qualquer:
curl -s https://api.ipify.org
# ou
dig +short myip.opendns.com @resolver1.opendns.com
```

---

## Resoluções Rápidas

### ⚡ Cenário 1: Port-Forward NÃO Está Ativo (MAIS COMUM)
```bash
# Symptoma: telnet files.spigo.net 2222 → Connection refused

# Solução:
# 1. Acesse seu roteador (192.168.1.1)
# 2. Vá para Port Forwarding
# 3. Configure:
#    Porta Externa: 2222
#    Porta Interna: 2222
#    IP Interno: <IP do seu PC com container>
#    Status: Ativo
# 4. Salve e reinicie se necessário
```

---

### ⚡ Cenário 2: DNS Não Resolve
```bash
# Symptoma: nslookup files.spigo.net → Host not found

# Solução: Usar IP Público ao invés de DNS
SFTP_HOST=<seu-ip-publico>   # ex: 203.0.113.45

# Descobrir IP público:
curl -s https://api.ipify.org
```

---

### ⚡ Cenário 3: Firewall Bloqueando Porta
```bash
# Symptoma: telnet files.spigo.net 2222 → Timeout (demora)

# Solução:
sudo ufw allow 2222
docker restart cdn_uploader
```

---

### ⚡ Cenário 4: Container Fora do Ar
```bash
# Symptoma: docker ps → Container NÃO aparece

# Solução:
docker restart cdn_uploader
docker logs cdn_uploader  # Ver erros

# Ou reconstruir:
docker-compose down sftp
docker-compose up -d sftp
```

---

## Fluxo de Diagnóstico Passo a Passo

### Passo 1: Teste SFTP (Em Produção)
Execute em `/home2/spigo594/apps/`:
```bash
php artisan sftp:test
```

**Resultado:**
- ✅ Sucesso → Pule para "Próximos Passos"
- ❌ Falha → Continue no Passo 2

---

### Passo 2: Teste DNS (Em Produção)
Execute em `/home2/spigo594/`:
```bash
nslookup files.spigo.net
# ou
dig files.spigo.net
```

**Resultado esperado:** Retorna um IP (seu IP público)

**Se retornar erro:**
- Solução rápida: Usar IP público em `.env`
  ```
  SFTP_HOST=203.0.113.45   # Seu IP real
  ```
- Continue no Passo 3

---

### Passo 3: Teste Conectividade (Em Produção)
Execute em `/home2/spigo594/`:
```bash
telnet files.spigo.net 2222
# ou
nc -zv files.spigo.net 2222
```

**Resultado esperado:** "Connection successful" ou "succeeded"

**Se falhar com "Connection refused":**
→ **Ativar Port-Forward** (veja Cenário 1 acima)

**Se falhar com timeout:**
→ **Liberar Firewall** (veja Cenário 3 acima)

---

### Passo 4: Teste SSH (Em Produção)
Execute em `/home2/spigo594/`:
```bash
ssh -p 2222 cdnuser@files.spigo.net
# Digite senha: GuMa1726
```

**Resultado esperado:** Prompt SSH da máquina remota

**Se falhar:** Verificar credenciais (Cenário 4)

---

### Passo 5: Verificar Container (NA SUA REDE)
```bash
# Container está rodando?
docker ps | grep cdn_uploader

# Ver logs
docker logs cdn_uploader

# Verificar porta
docker port cdn_uploader
```

---

## Próximos Passos

1. **Execute:** `php artisan sftp:test`
2. **Se falhar:** Execute os 5 passos de diagnóstico acima
3. **Baseado no resultado:** Aplique o cenário correspondente
4. **Teste novamente:** `php artisan sftp:test`
5. **Entregue o trabalho:** Teste funcionalidade no módulo ANT

## Logs e Debugging

### Logs de SFTP (Em Produção)
```bash
# Ver em tempo real
tail -f /home2/spigo594/apps/storage/logs/laravel.log | grep -i sftp

# Procure por estas mensagens:
# SFTP Upload Attempt      → Tentou conectar
# SFTP Connection Failed   → Falhou na conexão
# SFTP Upload Failed       → Falhou no upload
```

---

## Arquivos Modificados

1. `/app/Console/Commands/TestSftpConnection.php`
   - Comando: `php artisan sftp:test`
   - Diagnóstico rápido de conectividade

2. `/app/Modules/ANT/Services/TrabalhoUploadService.php`
   - Service com tratamento robusto de erro
   - Testa conectividade antes de fazer upload

3. `/app/Modules/ANT/Http/Controllers/TrabalhoController.php`
   - Melhor tratamento de erro de SFTP
   - Mensagens claras ao usuário

---

## Resumo: O Que Verificar

| Item | O Que Testar | Comando |
|------|-------------|---------|
| SFTP Geral | Tudo de uma vez | `php artisan sftp:test` |
| DNS | Resolve hostname | `nslookup files.spigo.net` |
| Conectividade | Porta 2222 aberta | `telnet files.spigo.net 2222` |
| SSH | Login com credenciais | `ssh -p 2222 cdnuser@files.spigo.net` |
| Container | Está rodando | `docker ps \| grep cdn_uploader` |
| Port-Forward | Redirecionamento ativo | `docker port cdn_uploader` |
| Logs | Erros na inicialização | `docker logs cdn_uploader` |

---

**Última atualização:** 25/05/2026
