# Módulo StreamingManager — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **StreamingManager** gerencia assinaturas de serviços de streaming compartilhados (Netflix, Spotify, Disney+, etc.) entre membros de um grupo. Permite:

- Cadastrar **serviços de streaming** com custo mensal
- Adicionar **membros** que compartilham a assinatura
- Registrar **pagamentos** de cada membro
- **Aprovar pagamentos** e calcular o rateio de custos
- Visualizar um **ranking** de pagamentos

**Acesso:** Público (sem autenticação necessária).

---

## 2. Estrutura de Diretórios

```
app/Modules/StreamingManager/
├── Http/
│   └── Controllers/
│       ├── MemberController.php      # Adicionar/remover membros
│       ├── PaymentController.php     # Registrar e aprovar pagamentos
│       └── StreamingController.php   # CRUD de serviços
├── Models/
│   ├── Streaming.php                # Serviço de streaming
│   ├── StreamingMember.php          # Membro do grupo
│   └── StreamingPayment.php         # Registro de pagamento
├── database/
│   └── migrations/                  # Migrations do módulo
├── resources/
│   └── views/
│       ├── create.blade.php         # Formulário de criação
│       ├── edit.blade.php           # Formulário de edição
│       ├── index.blade.php          # Lista de serviços
│       ├── show.blade.php           # Detalhes + membros + ranking
│       └── components/
│           └── layout.blade.php     # Layout do módulo
├── StreamingManagerServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/streaming-manager`  
**Middleware:** `web` (público)  
**Nome base:** `streaming.*`

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/streaming-manager` | `streaming.index` | Lista serviços de streaming |
| GET | `/streaming-manager/create` | `streaming.create` | Formulário de criação |
| POST | `/streaming-manager` | `streaming.store` | Salvar novo serviço |
| GET | `/streaming-manager/{streaming}` | `streaming.show` | Detalhes + membros + pagamentos |
| GET | `/streaming-manager/{streaming}/edit` | `streaming.edit` | Editar serviço |
| PUT | `/streaming-manager/{streaming}` | `streaming.update` | Atualizar serviço |
| DELETE | `/streaming-manager/{streaming}` | `streaming.destroy` | Excluir serviço |
| POST | `/streaming-manager/{streaming}/members` | `streaming.members.store` | Adicionar membro |
| DELETE | `/streaming-manager/members/{member}` | `streaming.members.destroy` | Remover membro |
| POST | `/streaming-manager/{streaming}/payments` | `streaming.payments.store` | Registrar pagamento |
| POST | `/streaming-manager/payments/{payment}/approve` | `streaming.payments.approve` | Aprovar pagamento |

---

## 4. Controllers

### `StreamingController`

| Método | Descrição |
|--------|-----------|
| `index()` | Lista todos os serviços cadastrados |
| `create()` | Exibe formulário de novo serviço |
| `store()` | Persiste novo serviço com nome, usuário, senha e custo mensal |
| `show()` | Exibe detalhes: membros, pagamentos e ranking de contribuições |
| `edit()` | Formulário de edição |
| `update()` | Atualiza dados do serviço |
| `destroy()` | Exclui serviço e dados relacionados |

### `MemberController`

| Método | Descrição |
|--------|-----------|
| `store()` | Adiciona usuário como membro do grupo de streaming |
| `destroy()` | Remove membro do grupo |

### `PaymentController`

| Método | Descrição |
|--------|-----------|
| `store()` | Registra pagamento de um membro (status: `pending`) |
| `approve()` | Aprova pagamento e marca como `approved` |

---

## 5. Models

### `Streaming`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` (responsável/dono) |
| `name` | string | Nome do serviço (ex: "Netflix") |
| `username` | string | Email/usuário da conta |
| `password` | string | Senha da conta |
| `monthly_cost` | decimal | Custo mensal total |

### `StreamingMember`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `streaming_id` | integer | FK para `Streaming` |
| `user_id` | integer | FK para `User` |
| `joined_at` | timestamp | Data de entrada no grupo |

### `StreamingPayment`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `streaming_id` | integer | FK para `Streaming` |
| `user_id` | integer | FK para `User` (pagador) |
| `amount` | decimal | Valor pago |
| `status` | string | `pending` ou `approved` |
| `date` | date | Data do pagamento |

---

## 6. Fluxo Principal

```
Criar serviço de streaming (nome, custo mensal, credenciais)
→ Adicionar membros que compartilham a conta
→ Cada membro registra seu pagamento mensal
→ Admin/dono aprova os pagamentos
→ Ranking mostra quem está em dia e quem está devendo
```

---

## 7. Views

| Arquivo | Descrição |
|---------|-----------|
| `index.blade.php` | Cartões com todos os serviços cadastrados |
| `create.blade.php` | Formulário de criação (nome, usuário, senha, custo) |
| `edit.blade.php` | Formulário de edição |
| `show.blade.php` | Detalhes completos: membros, histórico de pagamentos, ranking |
| `components/layout.blade.php` | Layout próprio do módulo |

---

## 8. Notas para Agentes de IA

- Este módulo é **totalmente público** — não requer autenticação. Qualquer pessoa pode criar serviços e registrar pagamentos.
- As migrations ficam dentro do módulo em `database/migrations/`.
- Não há middleware `RegistrarAcesso` — os acessos não são rastreados no módulo Métricas.
- A senha (`password`) do streaming é armazenada para exibição aos membros — considere usar `encrypt()`/`decrypt()` se implementar segurança adicional.
- O módulo usa layout próprio (`components/layout.blade.php`) em vez do layout global da aplicação.
- Para implementar autenticação neste módulo, adicione o middleware `auth` ao grupo de rotas em `routes.php`.
