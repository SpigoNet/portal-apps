# API Mithril — Documentação

> Gestão Financeira Pessoal — Endpoints JSON

## Autenticação

Todos os endpoints requerem autenticação via **Laravel Sanctum**.

**Header requerido:**
```
Authorization: Bearer <seu_token>
```

---

## Endpoints

### 1. Dashboard

#### `GET /api/mithril/dashboard`

Retorna dados agregados do mês atual: contas, saldo, transações e pré-transações pendentes.

**Response:**
```json
{
  "data": {
    "periodo": {
      "mes": 4,
      "ano": 2026,
      "label": "abril de 2026"
    },
    "contas": [
      {
        "id": 1,
        "nome": "Nubank",
        "tipo": "normal",
        "saldo_inicial": 1000.00,
        "real_hoje": 1500.00,
        "previsto_hoje": 1500.00,
        "real_fim_mes": 1200.00,
        "previsto_fim_mes": 1200.00
      }
    ],
    "cartoes": [...],
    "transacoes_mes": 15,
    "pre_transacoes_pendentes": 5
  }
}
```

---

### 2. Contas

#### `GET /api/mithril/contas`

Lista todas as contas do usuário (corrente, poupança, cartão).

**Parâmetros:** none

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "nome": "Nubank",
      "tipo": "normal",
      "saldo_inicial": 1000.00,
      "dia_fechamento": 25,
      "dia_vencimento": 30,
      "created_at": "2026-01-01T00:00:00+00:00"
    }
  ]
}
```

#### `POST /api/mithril/contas`

Cria uma nova conta.

**Body:**
```json
{
  "nome": "Nubank",
  "tipo": "normal",
  "saldo_inicial": 1000.00,
  "dia_fechamento": 25,
  "dia_vencimento": 30
}
```

| Campo | Tipo | Obrigatório | Descrição |
|------|------|-------------|-----------|
| nome | string | sim | Nome da conta |
| tipo | string | sim | `normal` ou `credito` |
| saldo_inicial | number | não | Saldo inicial (padrão: 0) |
| dia_fechamento | int | não | Dia do fechamento (1-31) |
| dia_vencimento | int | não | Dia do vencimento (1-31) |

**Response (201):**
```json
{
  "data": { "id": 1, "nome": "Nubank", ... },
  "message": "Conta criada com sucesso."
}
```

#### `GET /api/mithril/contas/{id}`

Detalha uma conta específica.

#### `PUT /api/mithril/contas/{id}`

Atualiza uma conta.

#### `DELETE /api/mithril/contas/{id}`

Remove uma conta.

---

### 3. Transações

#### `GET /api/mithril/transacoes`

Lista transações do mês com totais.

**Parâmetros query:**
| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| mes | atual | Mês (1-12) |
| ano | atual | Ano |
| conta_id | todos | Filtrar por conta |

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "descricao": "Salário",
      "valor": 5000.00,
      "data_efetiva": "2026-04-01",
      "conta": { "id": 1, "nome": "Nubank" },
      "pre_transacao_id": null,
      "created_at": "2026-04-01T10:00:00+00:00"
    }
  ],
  "meta": {
    "mes": 4,
    "ano": 2026,
    "total_entradas": 5000.00,
    "total_saidas": -1500.00,
    "saldo_mes": 3500.00,
    "total_registros": 15
  }
}
```

#### `POST /api/mithril/transacoes`

Cria uma transação手动.

**Body:**
```json
{
  "descricao": "Supermercado",
  "valor": 250.00,
  "data_efetiva": "2026-04-15",
  "conta_id": 1,
  "operacao": "debito"
}
```

| Campo | Tipo | Obrigatório | Descrição |
|------|------|-------------|-----------|
| descricao | string | sim | Descrição |
| valor | number | sim | Valor |
| data_efetiva | date | sim | Data (YYYY-MM-DD) |
| conta_id | int | sim | ID da conta |
| operacao | string | sim | `debito` ou `credito` |

**Nota:** Valores de saída são convertidos para negativo automaticamente.

---

### 4. Pré-transações

> Lançamentos recorrentes ou parcelados que serão efetivados no futuro.

#### `GET /api/mithril/pre-transacoes`

Lista todas as pré-transações (ativas e inativas).

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "descricao": "Netflix",
      "valor_parcela": -55.90,
      "conta": { "id": 1, "nome": "Nubank" },
      "dia_vencimento": 15,
      "tipo": "recorrente",
      "total_parcelas": null,
      "parcela_atual": 0,
      "ativa": true,
      "data_inicio": "2026-01-01",
      "data_ultima_acao": null,
      "created_at": "2026-01-01T00:00:00+00:00"
    }
  ]
}
```

#### `POST /api/mithril/pre-transacoes`

Cria uma pré-transação.

**Body (recorrente):**
```json
{
  "descricao": "Netflix",
  "valor_parcela": 55.90,
  "conta_id": 1,
  "dia_vencimento": 15,
  "tipo": "recorrente",
  "operacao": "debito"
}
```

**Body (parcelada):**
```json
{
  "descricao": "iPhone 16x",
  "valor_parcela": 500.00,
  "conta_id": 1,
  "dia_vencimento": 20,
  "tipo": "parcelada",
  "operacao": "debito",
  "total_parcelas": 12,
  "data_inicio": "2026-04-01"
}
```

| Campo | Tipo | Obrigatório | Descrição |
|------|------|-------------|-----------|
| descricao | string | sim | Descrição |
| valor_parcela | number | sim | Valor de cada parcela |
| conta_id | int | sim | ID da conta |
| dia_vencimento | int | sim | Dia do mês (1-31) |
| tipo | string | sim | `recorrente` ou `parcelada` |
| operacao | string | sim | `debito` ou `credito` |
| total_parcelas | se parcelada | Total de parcelas |
| data_inicio | se parcelada | Data início |

#### `PUT /api/mithril/pre-transacoes/{id}`

Atualiza uma pré-transação.

#### `DELETE /api/mithril/pre-transacoes/{id}`

Remove uma pré-transação (definitivo).

#### `POST /api/mithril/pre-transacoes/{id}/toggle`

Alterna status ativo/inativo.

**Response:**
```json
{
  "data": { "id": 1, "ativa": false, ... },
  "message": "Pré-transação desativada."
}
```

#### `POST /api/mithril/pre-transacoes/{id}/efetivar`

Efetiva uma parcela, criando uma `Transacao` real.

**Parâmetros query:**
| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| mes | atual | Mês da efetivação |
| ano | atual | Ano da efetivação |

**Response (201):**
```json
{
  "data": {
    "id": 45,
    "descricao": "Netflix",
    "valor": -55.90,
    "data_efetiva": "2026-04-15"
  },
  "message": "Parcela efetivada com sucesso."
}
```

---

### 5. Lançamentos

#### `GET /api/mithril/lancamentos`

Lista combinada de transações efetivadas + pré-transações projetadas.

**Parâmetros query:**
| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| mes | atual | Mês |
| ano | atual | Ano |
| conta_id | todos | Filtrar por conta |

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "pre_transacao_id": null,
      "descricao": "Salário",
      "valor": 5000.00,
      "data_efetiva": "2026-04-01",
      "conta": { "id": 1, "nome": "Nubank" },
      "status": "efetivado",
      "tipo": "real",
      "meta_parcela": null,
      "saldo_acumulado_efetivado": 5000.00,
      "saldo_acumulado_previsto": 5000.00
    },
    {
      "id": null,
      "pre_transacao_id": 5,
      "descricao": "Netflix",
      "valor": -55.90,
      "data_efetiva": "2026-04-15",
      "conta": { "id": 1, "nome": "Nubank" },
      "status": "pendente",
      "tipo": "projetado",
      "meta_parcela": null,
      "saldo_acumulado_efetivado": 5000.00,
      "saldo_acumulado_previsto": 4944.10
    }
  ],
  "meta": {
    "mes": 4,
    "ano": 2026,
    "saldo_inicial": 1000.00,
    "saldo_acumulado_efetivado": 3500.00,
    "saldo_acumulado_previsto": 3200.00,
    "total_registros": 15
  }
}
```

**Tipos de status:**
- `efetivado` — transação já concretizada
- `pendente` — pré-transação ainda não confirmada
- `confirmado` — pré-transação confirmada para este mês

---

### 6. Fechamentos

#### `GET /api/mithril/fechamentos`

Lista contas com sugestão de saldo para fechamento.

**Response:**
```json
{
  "data": [
    {
      "conta": { "id": 1, "nome": "Nubank" },
      "ultimo_fechamento": {
        "mes": 3,
        "ano": 2026,
        "saldo_final": 1500.00
      },
      "alvo_mes": 4,
      "alvo_ano": 2026,
      "alvo_data_formatada": "abril de 2026",
      "saldo_inicial": 1500.00,
      "movimentacoes": 350.00,
      "saldo_sugerido": 1850.00
    }
  ]
}
```

#### `POST /api/mithril/fechamentos`

Registra um fechamento de mês.

**Body:**
```json
{
  "conta_id": 1,
  "mes": 3,
  "ano": 2026,
  "saldo_final": 1500.00
}
```

| Campo | Tipo | Obrigatório | Descrição |
|------|------|-------------|-----------|
| conta_id | int | sim | ID da conta |
| mes | int | sim | Mês (1-12) |
| ano | int | sim | Ano |
| saldo_final | number | sim | Saldo final do mês |

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "conta_id": 1,
    "mes": 3,
    "ano": 2026,
    "saldo_final": 1500.00,
    "data_fechamento": "2026-04-01"
  },
  "message": "Fechamento registrado com sucesso."
}
```

---

## Códigos de Erro

| Código | Descrição |
|--------|-----------|
| 401 | Não autenticado |
| 403 | Acesso negado |
| 404 | Recurso não encontrado |
| 422 | Validação falhou |
| 500 | Erro interno |

**Exemplo erro 422:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "descricao": ["The descricao field is required."]
  }
}
```