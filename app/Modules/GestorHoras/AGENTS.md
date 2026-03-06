# Módulo GestorHoras — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **GestorHoras** é um sistema de gestão de contratos e controle de horas trabalhadas. Permite:

- Criar e gerenciar **contratos** de serviço com clientes
- Registrar **apontamentos** de horas trabalhadas em cada contrato
- Calcular o valor total faturável com base nas horas e valor/hora
- Compartilhar um **link público com token** para o cliente acompanhar o contrato

**Acesso:** Autenticado para gestão. URL pública com token para o cliente visualizar.  
**Middleware:** `RegistrarAcesso:GestorHoras`

---

## 2. Estrutura de Diretórios

```
app/Modules/GestorHoras/
├── Http/
│   └── Controllers/
│       ├── ApontamentoController.php     # Registro de horas
│       └── ContratoController.php        # CRUD de contratos + dashboard
├── Models/
│   ├── Apontamento.php                  # Registro de horas trabalhadas
│   ├── Cliente.php                      # Cliente/empresa
│   ├── Contrato.php                     # Contrato de serviço
│   └── ContratoItem.php                 # Item/linha do contrato
├── resources/
│   └── views/
│       ├── create.blade.php             # Formulário de criação de contrato
│       ├── index.blade.php              # Dashboard com lista de contratos
│       ├── show.blade.php               # Detalhes do contrato + apontamentos
│       ├── public_dashboard.blade.php   # View pública para o cliente
│       └── partials/
│           └── card-contrato.blade.php  # Card de resumo do contrato
├── GestorHorasServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/gestor-horas`  
**Middleware padrão:** `web`, `auth`, `RegistrarAcesso:GestorHoras`  
**Nome base:** `gestor-horas.*`

| Método | URI | Middleware | Nome | Descrição |
|--------|-----|-----------|------|-----------|
| GET | `/gestor-horas/acompanhamento/{token}` | público | `gestor-horas.public` | Dashboard público para o cliente |
| GET | `/gestor-horas` | auth | `gestor-horas.index` | Dashboard com lista de contratos |
| GET | `/gestor-horas/novo-contrato` | auth | `gestor-horas.create` | Formulário de criação |
| POST | `/gestor-horas/novo-contrato` | auth | `gestor-horas.store` | Salvar novo contrato |
| GET | `/gestor-horas/contrato/{id}` | auth | `gestor-horas.show` | Detalhes do contrato |
| POST | `/gestor-horas/contrato/{id}/apontar` | auth | `gestor-horas.apontar` | Registrar horas |

---

## 4. Controllers

### `ContratoController`

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET `/gestor-horas` | Lista todos os contratos do usuário com resumo de horas |
| `create()` | GET `/gestor-horas/novo-contrato` | Exibe formulário de criação de contrato |
| `store()` | POST `/gestor-horas/novo-contrato` | Persiste novo contrato no banco |
| `show()` | GET `/gestor-horas/contrato/{id}` | Exibe detalhes do contrato com apontamentos |
| `publicDashboard()` | GET `/gestor-horas/acompanhamento/{token}` | View pública com token para o cliente |

**Acesso:** Controlado via Gates no controller para garantir que o usuário acesse apenas seus próprios contratos.

### `ApontamentoController`

| Método | Rota | Descrição |
|--------|------|-----------|
| `store()` | POST `/gestor-horas/contrato/{id}/apontar` | Registra horas trabalhadas no contrato |

---

## 5. Models

### `Contrato`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `cliente_id` | integer | FK para `Cliente` |
| `descricao` | text | Descrição do serviço contratado |
| `valor_total` | decimal | Valor total do contrato |
| `data_inicio` | date | Data de início |
| `data_fim` | date | Data de encerramento prevista |
| `status` | string | `ativo`, `encerrado`, `pausado` |
| `token` | string | Token único para acesso público |

### `Apontamento`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `contrato_id` | integer | FK para `Contrato` |
| `data` | date | Data do trabalho |
| `horas` | decimal | Horas trabalhadas |
| `descricao` | text | Descrição das atividades |
| `valor_hora` | decimal | Valor cobrado por hora |

### `Cliente`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `nome` | string | Nome do cliente/empresa |
| `email` | string | Email de contato |
| `telefone` | string | Telefone |

### `ContratoItem`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `contrato_id` | integer | FK para `Contrato` |
| `descricao` | string | Descrição do item |
| `valor_unitario` | decimal | Valor unitário |
| `quantidade` | decimal | Quantidade |

---

## 6. Fluxo Principal

```
Usuário cria contrato com cliente e dados financeiros
→ Registra horas trabalhadas via apontamentos
→ Sistema calcula total faturado vs. total contratado
→ Compartilha link público (token) com o cliente para acompanhamento
```

---

## 7. Acesso Público com Token

Cada `Contrato` possui um campo `token` gerado automaticamente. O link `/gestor-horas/acompanhamento/{token}` é público (sem autenticação) e exibe ao cliente o resumo do contrato e os apontamentos registrados, sem permissão de edição.

---

## 8. Notas para Agentes de IA

- O acesso aos contratos é escopo por usuário — o usuário só vê seus próprios contratos.
- O controle de acesso é feito via Gates do Laravel dentro dos controllers.
- A rota pública `/acompanhamento/{token}` **não** requer autenticação.
- O middleware `RegistrarAcesso:GestorHoras` rastreia acessos para as métricas do sistema.
- Para adicionar novas funcionalidades (ex: relatórios, faturamento), siga o padrão MVC: crie controller → retorne view (sem API REST).
