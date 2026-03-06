# Módulo Mithril — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **Mithril** é um sistema de gestão financeira pessoal. Permite:

- Gerenciar **contas** (corrente, poupança, cartão de crédito)
- Registrar **transações** (entradas e saídas)
- Criar **pré-transações** (lançamentos recorrentes ou parcelados)
- Confirmar e efetivar pré-transações no período correto
- Controlar **fechamentos** periódicos de conta
- Gerenciar **faturas** de cartão de crédito

**Acesso:** Autenticado (`auth`).  
**Middleware:** `RegistrarAcesso:Mithril`

---

## 2. Estrutura de Diretórios

```
app/Modules/Mithril/
├── Http/
│   └── Controllers/
│       ├── DashboardController.php          # Dashboard principal
│       ├── FechamentoController.php         # Fechamentos periódicos
│       ├── LancamentoController.php         # Listagem de transações
│       ├── PreTransacaoAcoesController.php  # Confirmar/efetivar pré-transações
│       ├── PreTransacaoController.php       # CRUD de pré-transações
│       └── TransacaoController.php          # Gerenciamento de transações
├── Models/
│   ├── Classificacao.php                   # Categoria de transação
│   ├── Conta.php                           # Conta bancária/cartão
│   ├── CartaoFaturaItem.php                # Item de fatura de cartão
│   ├── Fatura.php                          # Fatura/boleto
│   ├── PreTransacao.php                    # Lançamento futuro (parcelado/recorrente)
│   ├── RegraDescricao.php                  # Regra para classificação automática
│   ├── SaldoFechamento.php                 # Saldo no fechamento
│   └── Transacao.php                       # Transação concluída
├── resources/
│   └── views/
│       ├── dashboard/
│       │   └── index.blade.php             # Dashboard principal
│       ├── pre_transacoes/                 # CRUD de pré-transações
│       ├── fechamentos/                    # Controle de fechamentos
│       ├── lancamentos/                    # Listagem de lançamentos
│       └── components/                     # Componentes de layout
├── MithrilServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/mithril`  
**Middleware:** `web`, `auth`, `RegistrarAcesso:Mithril`  
**Nome base:** `mithril.*`

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/mithril` | `mithril.dashboard` | Dashboard principal |
| GET | `/mithril/set-theme` | `mithril.set-theme` | Preferência de tema |
| GET | `/mithril/lancamentos` | `mithril.lancamentos` | Lista de lançamentos |
| GET | `/mithril/transacao/criar` | `mithril.transacao.criar` | (placeholder) Criar transação |
| GET | `/mithril/fatura/{id}` | `mithril.fatura.show` | (placeholder) Ver fatura |
| GET | `/mithril/pre-transacoes` | `mithril.pre-transacoes.index` | Listar pré-transações |
| POST | `/mithril/pre-transacoes` | `mithril.pre-transacoes.store` | Criar pré-transação |
| GET | `/mithril/pre-transacoes/{id}/toggle` | `mithril.pre-transacoes.toggle` | Ativar/desativar |
| GET | `/mithril/pre-transacoes/{id}/confirmar` | `mithril.pre-transacoes.confirmar` | Formulário de confirmação |
| POST | `/mithril/pre-transacoes/{id}/confirmar` | `mithril.pre-transacoes.confirmar.store` | Confirmar pré-transação |
| POST | `/mithril/pre-transacoes/{id}/efetivar` | `mithril.pre-transacoes.efetivar` | Efetivar lançamento |
| GET | `/mithril/fechamentos` | `mithril.fechamentos.index` | Listar fechamentos |
| POST | `/mithril/fechamentos` | `mithril.fechamentos.store` | Criar fechamento |

---

## 4. Controllers

### `DashboardController`
Exibe o painel principal com visão geral das contas, saldo atual e transações recentes. Também gerencia a preferência de tema do usuário.

### `PreTransacaoController`
CRUD de pré-transações — lançamentos futuros que ainda não foram efetivados no banco. Suporta dois tipos:
- **Parcelada:** número fixo de parcelas com valor por parcela
- **Recorrente:** lançamento que se repete indefinidamente

### `PreTransacaoAcoesController`
Gerencia as ações sobre pré-transações:
- **Confirmar:** Preenche data e conta para efetivação
- **Efetivar:** Cria a `Transacao` real e decrementa o saldo da conta

### `FechamentoController`
Gerencia fechamentos periódicos (mensais) de conta, registrando o saldo no momento do fechamento.

### `LancamentoController`
Lista as transações efetivadas com filtros por período, conta e categoria.

### `TransacaoController`
Gerenciamento direto de transações (em desenvolvimento).

---

## 5. Models

### `PreTransacao`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` |
| `descricao` | string | Descrição do lançamento |
| `valor_parcela` | decimal | Valor de cada parcela |
| `conta_id` | integer | FK para `Conta` |
| `dia_vencimento` | integer | Dia do mês para vencimento |
| `tipo` | string | `parcelada` ou `recorrente` |
| `total_parcelas` | integer | Total de parcelas (somente parcelada) |
| `parcelas_pagas` | integer | Parcelas já efetivadas |
| `ativa` | boolean | Se a pré-transação está ativa |

### `Transacao`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` |
| `conta_id` | integer | FK para `Conta` |
| `classificacao_id` | integer | FK para `Classificacao` |
| `descricao` | string | Descrição da transação |
| `valor` | decimal | Valor (positivo = entrada, negativo = saída) |
| `data` | date | Data da transação |
| `tipo` | string | `entrada` ou `saida` |

### `Conta`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` |
| `nome` | string | Nome da conta |
| `tipo` | string | `corrente`, `poupanca`, `cartao` |
| `saldo` | decimal | Saldo atual |

### `Fatura`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `conta_id` | integer | FK para `Conta` (cartão) |
| `periodo` | string | Ex: "2026-01" |
| `data_vencimento` | date | Vencimento da fatura |
| `valor_total` | decimal | Total a pagar |

### `Classificacao`
Categorias de transação (ex: Alimentação, Transporte, Lazer).

### `RegraDescricao`
Regras para classificação automática de transações com base na descrição.

### `SaldoFechamento`
Saldo registrado no momento de cada fechamento periódico.

### `CartaoFaturaItem`
Item individual de uma fatura de cartão de crédito.

---

## 6. Fluxo Principal

```
Cadastrar contas → Criar pré-transações (parceladas/recorrentes)
→ Confirmar e efetivar lançamentos no período correto
→ Ver dashboard com saldo e movimentações
→ Realizar fechamento periódico
```

---

## 7. Escopo por Usuário

Todos os models do Mithril usam **Global Scope** para filtrar automaticamente por `user_id` do usuário autenticado. Não é necessário filtrar manualmente — cada usuário vê apenas seus próprios dados.

---

## 8. Notas para Agentes de IA

- O módulo usa `RegistrarAcesso:Mithril` para rastreamento no módulo Métricas.
- O `user_id` é associado automaticamente aos models via Global Scope.
- Pré-transações **não são** transações — elas precisam ser efetivadas para criar uma `Transacao` real.
- Algumas rotas estão marcadas como placeholder (ex: `/transacao/criar`, `/fatura/{id}`) e ainda estão em desenvolvimento.
- As migrations do módulo estão nas **migrations globais** (`database/migrations/`), não dentro do módulo.
