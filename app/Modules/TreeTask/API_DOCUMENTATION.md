# TreeTask API - Documentação

## Visão Geral

API RESTful para gerenciamento de projetos e tarefas no estilo Kanban/Árvore. Toda a API retorna respostas em formato JSON.

**Base URL:** `/treetask/api/v1`

---

## Autenticação

A autenticação é feita via **Token MD5**. O token é gerado a partir do hash MD5 do email do usuário concatenado com a senha criptografada do banco de dados.

### Headers Obrigatórios

| Header | Descrição | Exemplo |
|--------|-----------|---------|
| `X-User-ID` | ID do usuário no sistema | `1` |
| `X-Token` | Token MD5(email + password_hash) | `a1b2c3d4e5f6...` |

### Gerando o Token

#### PHP
```php
$user = User::find(1);
$token = md5($user->email . $user->password);
```

#### JavaScript
```javascript
// Supondo que você tenha o email e o hash da senha
const crypto = require('crypto');
const token = crypto.createHash('md5')
  .update(email + passwordHash)
  .digest('hex');
```

#### Python
```python
import hashlib

token = hashlib.md5((email + password_hash).encode()).hexdigest()
```

### Exemplo de Requisição

```bash
curl -X GET \
  -H "X-User-ID: 1" \
  -H "X-Token: 9e107d9d372bb6826bd81d3542a419d6" \
  https://sua-api.com/treetask/api/v1/projetos
```

### Resposta de Erro (401)

```json
{
  "success": false,
  "message": "Token inválido."
}
```

---

## Conceitos Principais

### 1. Projeto

Um **Projeto** é a entidade principal do sistema. Representa um objetivo ou trabalho a ser realizado, como "Desenvolver Website da Empresa" ou "Lançar Novo Produto".

#### Atributos
- **id_projeto**: Identificador único
- **nome**: Nome do projeto
- **descricao**: Descrição detalhada
- **status**: Situação atual (Planejamento, Em Andamento, Concluído, etc.)
- **data_inicio**: Data de início
- **data_prevista_termino**: Prazo estimado
- **data_conclusao_real**: Data real de conclusão
- **id_user_owner**: ID do dono/responsável pelo projeto

### 2. Fase

Uma **Fase** representa uma etapa do projeto, similar a colunas em um quadro Kanban. Cada fase contém tarefas relacionadas.

#### Exemplos de Fases
- "A Fazer" (To Do)
- "Em Andamento" (Doing)
- "Em Revisão"
- "Concluído" (Done)

#### Atributos
- **id_fase**: Identificador único
- **id_projeto**: ID do projeto pai
- **nome**: Nome da fase
- **descricao**: Descrição opcional
- **status**: Estado da fase
- **ordem**: Posição da fase no quadro (ordenação)

### 3. Tarefa

Uma **Tarefa** é a unidade de trabalho mínima. Representa uma atividade específica a ser executada dentro de uma fase.

#### Exemplos de Tarefas
- "Criar wireframes da homepage"
- "Implementar login com Google"
- "Revisar documentação técnica"

#### Atributos
- **id_tarefa**: Identificador único
- **id_fase**: ID da fase onde está localizada
- **titulo**: Título da tarefa
- **descricao**: Descrição detalhada
- **status**: Situação atual (A Fazer, Em Andamento, Concluído, etc.)
- **id_user_responsavel**: ID do usuário responsável
- **prioridade**: Baixa, Média, Alta ou Urgente
- **data_vencimento**: Prazo limite
- **estimativa_tempo**: Tempo estimado em horas
- **ordem**: Posição na fase (ordenação)

### 4. Anexo

Um **Anexo** é um arquivo vinculado a uma tarefa. Pode ser imagem, documento, planilha, etc.

#### Atributos
- **id_anexo**: Identificador único
- **id_user_upload**: ID do usuário que fez o upload
- **nome_arquivo**: Nome original do arquivo
- **path_arquivo**: Caminho de armazenamento
- **mime_type**: Tipo MIME do arquivo
- **tamanho**: Tamanho em bytes

---

## Endpoints

### Projetos

#### Listar Todos os Projetos
```
GET /treetask/api/v1/projetos
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "data": [
    {
      "id_projeto": 1,
      "nome": "Website Empresa XYZ",
      "descricao": "Desenvolvimento do site institucional",
      "status": "Em Andamento",
      "data_inicio": "2026-02-01",
      "data_prevista_termino": "2026-03-15",
      "id_user_owner": 1,
      "owner": {
        "id": 1,
        "name": "João Silva"
      }
    }
  ]
}
```

#### Obter Detalhes de um Projeto
```
GET /treetask/api/v1/projetos/{id}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "data": {
    "id_projeto": 1,
    "nome": "Website Empresa XYZ",
    "fases": [
      {
        "id_fase": 1,
        "nome": "A Fazer",
        "tarefas": [...]
      }
    ]
  }
}
```

#### Criar Projeto
```
POST /treetask/api/v1/projetos
```

**Body (JSON):**
```json
{
  "nome": "Novo Projeto",
  "descricao": "Descrição do projeto",
  "data_inicio": "2026-02-15",
  "data_prevista_termino": "2026-04-30"
}
```

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Projeto criado com sucesso.",
  "data": {
    "id_projeto": 2,
    "nome": "Novo Projeto",
    ...
  }
}
```

#### Atualizar Projeto
```
PUT /treetask/api/v1/projetos/{id}
```

**Body (JSON):**
```json
{
  "nome": "Nome Atualizado",
  "status": "Concluído",
  "data_conclusao_real": "2026-02-10"
}
```

#### Excluir Projeto
```
DELETE /treetask/api/v1/projetos/{id}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Projeto excluído com sucesso."
}
```

---

### Fases

#### Listar Fases de um Projeto
```
GET /treetask/api/v1/projetos/{id_projeto}/fases
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id_fase": 1,
      "nome": "A Fazer",
      "ordem": 0,
      "tarefas": [...]
    },
    {
      "id_fase": 2,
      "nome": "Em Andamento",
      "ordem": 1,
      "tarefas": [...]
    }
  ]
}
```

#### Criar Fase
```
POST /treetask/api/v1/fases
```

**Body (JSON):**
```json
{
  "id_projeto": 1,
  "nome": "Em Revisão",
  "descricao": "Tarefas aguardando revisão"
}
```

#### Obter Detalhes da Fase
```
GET /treetask/api/v1/fases/{id}
```

#### Atualizar Fase
```
PUT /treetask/api/v1/fases/{id}
```

**Body (JSON):**
```json
{
  "nome": "Novo Nome da Fase",
  "ordem": 2
}
```

#### Excluir Fase
```
DELETE /treetask/api/v1/fases/{id}
```

---

### Tarefas

#### Listar Tarefas de uma Fase
```
GET /treetask/api/v1/fases/{id_fase}/tarefas
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id_tarefa": 1,
      "titulo": "Criar logo",
      "status": "A Fazer",
      "prioridade": "Alta",
      "responsavel": {
        "id": 2,
        "name": "Maria Souza"
      },
      "anexos": []
    }
  ]
}
```

#### Criar Tarefa
```
POST /treetask/api/v1/tarefas
```

**Body (JSON):**
```json
{
  "id_fase": 1,
  "titulo": "Implementar autenticação",
  "descricao": "Adicionar login com token",
  "id_user_responsavel": 2,
  "prioridade": "Alta",
  "data_vencimento": "2026-02-20",
  "estimativa_tempo": 8
}
```

#### Obter Detalhes da Tarefa
```
GET /treetask/api/v1/tarefas/{id}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id_tarefa": 1,
    "titulo": "Implementar autenticação",
    "fase": {
      "id_fase": 1,
      "nome": "Em Andamento",
      "projeto": {
        "id_projeto": 1,
        "nome": "Website Empresa"
      }
    },
    "responsavel": {...},
    "anexos": [...]
  }
}
```

#### Atualizar Tarefa
```
PUT /treetask/api/v1/tarefas/{id}
```

**Body (JSON):**
```json
{
  "titulo": "Título atualizado",
  "id_fase": 2,
  "status": "Em Andamento",
  "prioridade": "Urgente"
}
```

> **Nota:** Ao alterar `id_fase`, a tarefa é movida para outra fase (ex: de "A Fazer" para "Em Andamento").

#### Atualizar Status da Tarefa
```
PATCH /treetask/api/v1/tarefas/{id}/status
```

**Body (JSON):**
```json
{
  "status": "Concluído"
}
```

**Status permitidos:** `A Fazer`, `Em Andamento`, `Concluído`, `Planejamento`, `Aguardando resposta`

#### Excluir Tarefa
```
DELETE /treetask/api/v1/tarefas/{id}
```

---

### Anexos

#### Listar Anexos de uma Tarefa
```
GET /treetask/api/v1/tarefas/{id_tarefa}/anexos
```

#### Criar Anexo (Upload)
```
POST /treetask/api/v1/tarefas/{id_tarefa}/anexos
```

**Content-Type:** `multipart/form-data`

**Parâmetros:**
- `arquivo` (file): Arquivo a ser enviado (máx. 10MB)

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Anexo criado com sucesso.",
  "data": {
    "id_anexo": 1,
    "nome_arquivo": "documento.pdf",
    "mime_type": "application/pdf",
    "tamanho": 1024000
  }
}
```

#### Obter Detalhes do Anexo
```
GET /treetask/api/v1/anexos/{id}
```

#### Download do Anexo
```
GET /treetask/api/v1/anexos/{id_anexo}/download
```

**Resposta:** Arquivo binário com headers apropriados para download.

#### Excluir Anexo
```
DELETE /treetask/api/v1/tarefas/{id_tarefa}/anexos/{id_anexo}
```

---

## Endpoints de Integração (Alfred)

Endpoints especiais desenvolvidos para integração com sistemas externos, como o assistente virtual Alfred.

### Health Check

Verifica se a API está funcionando e conectada ao banco de dados.

```
GET /treetask/api/v1/health
```

**Resposta:**
```json
{
  "status": "ok",
  "database": "connected",
  "timestamp": "2026-02-15T10:30:00.000000Z"
}
```

---

### Listar Tarefas (Dashboard)

Lista tarefas com filtros avançados para uso em dashboards e painéis.

```
GET /treetask/api/v1/tarefas?filtro=pendentes&limit=5
```

**Parâmetros de Query:**

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `status` | string/array | Filtrar por status ou "nao_concluidas" | `Em Andamento`, `["A Fazer", "Em Andamento"]`, `nao_concluidas` |
| `prioridade` | string | Filtrar por prioridade | `Alta`, `Urgente` |
| `projeto_id` | integer | Filtrar por projeto | `1` |
| `responsavel_id` | integer | Filtrar por responsável | `2` |
| `vencimento_ate` | date | Tarefas que vencem até a data | `2026-02-20` |
| `limit` | integer | Limite de resultados (padrão: 50, max: 100) | `10` |

**Ordenação Padrão:**
1. Prioridade (Urgente > Alta > Média > Baixa)
2. Data de vencimento (mais próximas primeiro)

**Resposta:**
```json
{
  "tarefas": [
    {
      "id_tarefa": 123,
      "titulo": "Implementar login",
      "descricao": "Criar tela de autenticação",
      "status": "Em Andamento",
      "status_codigo": "andamento",
      "status_ordem": 3,
      "prioridade": "Alta",
      "prioridade_codigo": 3,
      "data_vencimento": "2026-02-20T00:00:00.000000Z",
      "data_criacao": "2026-02-10T10:00:00.000000Z",
      "data_atualizacao": "2026-02-14T15:30:00.000000Z",
      "estimativa_tempo": 4.5,
      "ordem": 1,
      "fase": {
        "id_fase": 5,
        "nome": "Em Andamento"
      },
      "projeto": {
        "id_projeto": 10,
        "nome": "Sistema Alfred"
      },
      "responsavel": {
        "id_user": 1,
        "nome": "Gustavo"
      }
    }
  ],
  "meta": {
    "total": 25,
    "filtro_aplicado": "nao_concluidas"
  }
}
```

---

### Relatório da Manhã (Top Tarefas)

Retorna as tarefas mais importantes para o período especificado. Ideal para notificações matinais.

```
GET /treetask/api/v1/tarefas/relatorio/manha?dias=7&limit=3
```

**Parâmetros de Query:**

| Parâmetro | Tipo | Descrição | Padrão |
|-----------|------|-----------|--------|
| `dias` | integer | Período em dias para frente | `7` |
| `limit` | integer | Quantidade máxima de tarefas | `3` |

**Filtros Aplicados:**
- Status diferente de "Concluído"
- Data de vencimento dentro do período OU sem data definida

**Ordenação:**
1. Prioridade (decrescente)
2. Data de vencimento (ascendente)
3. Ordem global (ascendente)

**Resposta:**
```json
{
  "tarefas": [...],
  "meta": {
    "total": 3,
    "periodo_dias": 7,
    "data_limite": "2026-02-22"
  }
}
```

---

### Tarefas Paradas

Identifica tarefas que estão "Em Andamento" mas sem atualização há X horas.

```
GET /treetask/api/v1/tarefas/paradas?horas=24
```

**Parâmetros de Query:**

| Parâmetro | Tipo | Descrição | Padrão |
|-----------|------|-----------|--------|
| `horas` | integer | Horas sem atualização | `24` |

**Resposta:**
```json
{
  "tarefas": [
    {
      "id_tarefa": 123,
      "titulo": "Configurar servidor",
      "status": "Em Andamento",
      "prioridade": "Alta",
      "horas_parada": 26,
      "ultima_atualizacao": "2026-02-14T08:30:00.000000Z",
      "fase": {...},
      "projeto": {...},
      "responsavel": {...}
    }
  ],
  "meta": {
    "total": 5,
    "horas_limite": 24,
    "data_corte": "2026-02-14T10:30:00.000000Z"
  }
}
```

---

### Tarefa Completa

Obtém todos os detalhes de uma tarefa específica com relacionamentos opcionais.

```
GET /treetask/api/v1/tarefas/{id}/completa?include=projeto,fase,responsavel,anexos
```

**Parâmetros de Query:**

| Parâmetro | Tipo | Descrição | Padrão |
|-----------|------|-----------|--------|
| `include` | string | Relacionamentos a incluir (separados por vírgula) | `projeto,fase,responsavel` |

**Opções de Include:**
- `projeto` - Dados do projeto
- `fase` - Dados da fase
- `responsavel` - Dados do responsável
- `anexos` - Lista de anexos

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id_tarefa": 123,
    "titulo": "Implementar autenticação",
    "descricao": "Criar login com token",
    "status": "Em Andamento",
    "status_codigo": "andamento",
    "prioridade": "Alta",
    "prioridade_codigo": 3,
    "data_vencimento": "2026-02-20T00:00:00.000000Z",
    "data_criacao": "2026-02-10T10:00:00.000000Z",
    "data_atualizacao": "2026-02-14T15:30:00.000000Z",
    "estimativa_tempo": 4.5,
    "ordem": 1,
    "id_fase": 5,
    "fase": {
      "id_fase": 5,
      "nome": "Em Andamento",
      "ordem": 2
    },
    "projeto": {
      "id_projeto": 10,
      "nome": "Sistema Alfred",
      "status": "Ativo"
    },
    "responsavel": {
      "id_user": 1,
      "nome": "Gustavo"
    },
    "anexos": [
      {
        "id_anexo": 1,
        "nome_arquivo": "especificacao.pdf",
        "mime_type": "application/pdf"
      }
    ]
  }
}
```

---

## Mapeamento de Status e Prioridade

### Status

| Status TreeTask | Código API | Ordem |
|-----------------|------------|-------|
| A Fazer | `pendente` | 1 |
| Planejamento | `pendente` | 2 |
| Em Andamento | `andamento` | 3 |
| Aguardando resposta | `andamento` | 4 |
| Concluído | `concluida` | 5 |

### Prioridade (Ordenação)

| Prioridade | Código | Valor |
|------------|--------|-------|
| Urgente | `4` | Maior |
| Alta | `3` | |
| Média | `2` | |
| Baixa | `1` | Menor |

---

## Exemplos de Uso (Alfred)

### Dashboard - Últimas 5 Tarefas Pendentes

```bash
curl -X GET \
  -H "X-User-ID: 1" \
  -H "X-Token: seu_token_aqui" \
  "https://sua-api.com/treetask/api/v1/tarefas?status=nao_concluidas&limit=5"
```

### Relatório Matinal - Top 3 Prioridades

```bash
curl -X GET \
  -H "X-User-ID: 1" \
  -H "X-Token: seu_token_aqui" \
  "https://sua-api.com/treetask/api/v1/tarefas/relatorio/manha?dias=7&limit=3"
```

### Verificar Tarefas Paradas (48h)

```bash
curl -X GET \
  -H "X-User-ID: 1" \
  -H "X-Token: seu_token_aqui" \
  "https://sua-api.com/treetask/api/v1/tarefas/paradas?horas=48"
```

### Health Check

```bash
curl -X GET \
  "https://sua-api.com/treetask/api/v1/health"
```

---

## Códigos de Status HTTP

| Código | Significado |
|--------|-------------|
| 200 | OK - Requisição bem-sucedida |
| 201 | Created - Recurso criado com sucesso |
| 400 | Bad Request - Dados inválidos enviados |
| 401 | Unauthorized - Token inválido ou ausente |
| 403 | Forbidden - Acesso negado |
| 404 | Not Found - Recurso não encontrado |
| 422 | Unprocessable Entity - Erro de validação |
| 500 | Internal Server Error - Erro do servidor |

---

## Exemplos de Fluxo Completo

### Cenário: Criar um Projeto Completo

```bash
# 1. Criar o projeto
POST /treetask/api/v1/projetos
{
  "nome": "App Mobile",
  "descricao": "Aplicativo para iOS e Android"
}

# 2. Criar fases para o projeto (ID: 1)
POST /treetask/api/v1/fases
{
  "id_projeto": 1,
  "nome": "Backlog"
}

POST /treetask/api/v1/fases
{
  "id_projeto": 1,
  "nome": "Em Desenvolvimento"
}

POST /treetask/api/v1/fases
{
  "id_projeto": 1,
  "nome": "Concluído"
}

# 3. Adicionar tarefas à primeira fase
POST /treetask/api/v1/tarefas
{
  "id_fase": 1,
  "titulo": "Configurar ambiente",
  "id_user_responsavel": 1,
  "prioridade": "Alta"
}

# 4. Mover tarefa para outra fase
PUT /treetask/api/v1/tarefas/1
{
  "id_fase": 2,
  "status": "Em Andamento"
}

# 5. Marcar como concluída
PATCH /treetask/api/v1/tarefas/1/status
{
  "status": "Concluído"
}
```

---

## Dicas e Boas Práticas

1. **Sempre verifique o usuário responsável**: Ao criar tarefas, informe o `id_user_responsavel` correto.

2. **Use a ordenação**: As fases e tarefas possuem campo `ordem` para organização visual.

3. **Prioridades**: Use as prioridades (Baixa, Média, Alta, Urgente) para indicar urgência.

4. **Datas**: Formato ISO 8601 (`YYYY-MM-DD`) para todas as datas.

5. **Anexos**: Limite de 10MB por arquivo. Formatos comuns suportados: PDF, imagens, Office.

6. **Cache do token**: O token permanece válido enquanto a senha do usuário não for alterada.

---

## Suporte

Para dúvidas ou problemas, entre em contato com a equipe de desenvolvimento.
