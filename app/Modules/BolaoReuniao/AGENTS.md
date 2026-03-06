# Módulo BolaoReuniao — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **BolaoReuniao** é um jogo de previsão para reuniões. Os participantes fazem palpites sobre o que vai acontecer em uma reunião (ex: duração, tópicos debatidos, decisões) e depois conferem o resultado.

**Acesso:** Público para participação e visualização de resultados. Autenticado para criar e encerrar reuniões.

---

## 2. Estrutura de Diretórios

```
app/Modules/BolaoReuniao/
├── Http/
│   └── Controllers/
│       └── BolaoController.php   # Controller único para toda a lógica
├── resources/
│   └── views/
│       ├── index.blade.php        # Página inicial com reuniões ativas
│       ├── participate.blade.php  # Formulário de participação/palpite
│       ├── results.blade.php      # Exibição dos resultados
│       ├── thank_you.blade.php    # Confirmação após palpite
│       └── layouts/
│           └── bolao.blade.php    # Layout específico do módulo
├── BolaoReuniaoServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/bolao`  
**Middleware:** `web` (rotas públicas); `auth` para criação/encerramento  
**Nome base das rotas:** `bolao.*`

| Método | URI | Middleware | Descrição |
|--------|-----|-----------|-----------|
| GET | `/bolao` | público | Lista reuniões ativas |
| GET | `/bolao/p/{id}` | público | Formulário de participação |
| POST | `/bolao/guess` | público | Submeter palpite |
| GET | `/bolao/results/{id}` | público | Ver resultados da reunião |
| GET | `/bolao/status/{id}` | público | Verificar status (AJAX) |
| POST | `/bolao/start` | `auth` | Criar nova reunião |
| POST | `/bolao/end/{id}` | `auth` | Encerrar reunião e registrar resultado |

---

## 4. Controllers

### `BolaoController`

Controller único que gerencia todo o ciclo de vida do bolão:

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET `/bolao` | Lista reuniões ativas para participação |
| `participate()` | GET `/bolao/p/{id}` | Exibe formulário de palpite para a reunião |
| `submitGuess()` | POST `/bolao/guess` | Salva o palpite do participante |
| `results()` | GET `/bolao/results/{id}` | Exibe resultados e ranking de palpites |
| `status()` | GET `/bolao/status/{id}` | Retorna status da reunião (JSON para AJAX) |
| `start()` | POST `/bolao/start` | Cria nova reunião (requer auth) |
| `end()` | POST `/bolao/end/{id}` | Encerra reunião e registra resultado real (requer auth) |

---

## 5. Models

Os models deste módulo são **globais**, localizados em `app/Models/`:

### `BolaoMeeting` (app/Models/)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `title` | string | Título/assunto da reunião |
| `description` | text | Descrição detalhada |
| `status` | string | `active`, `closed` |
| `result` | text | Resultado real (preenchido ao encerrar) |
| `created_by` | integer | FK para `User` (criador) |

### `BolaoGuess` (app/Models/)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `meeting_id` | integer | FK para `BolaoMeeting` |
| `participant_name` | string | Nome do participante |
| `guess` | text | Conteúdo do palpite |
| `score` | integer | Pontuação atribuída após resultado |

---

## 6. Fluxo Principal

```
Usuário autenticado cria reunião (POST /bolao/start)
→ Participantes fazem palpites (GET /bolao/p/{id} → POST /bolao/guess)
→ Participante vê confirmação (thank_you)
→ Criador encerra com resultado real (POST /bolao/end/{id})
→ Todos veem resultados e ranking (GET /bolao/results/{id})
```

---

## 7. Views

| Arquivo | Descrição |
|---------|-----------|
| `index.blade.php` | Lista de bolões ativos com link para participar |
| `participate.blade.php` | Formulário para enviar palpite |
| `thank_you.blade.php` | Confirmação de palpite recebido |
| `results.blade.php` | Resultados com palpites x resultado real |
| `layouts/bolao.blade.php` | Layout próprio (não usa `x-app-layout`) |

---

## 8. Notas para Agentes de IA

- Este é um dos módulos mais simples: um único controller, sem autenticação obrigatória para participar.
- Os models `BolaoMeeting` e `BolaoGuess` **não estão** dentro do diretório do módulo — estão em `app/Models/`.
- Não há middleware `RegistrarAcesso` neste módulo (acesso não é rastreado nas métricas).
- O módulo usa seu próprio layout (`layouts/bolao.blade.php`) em vez do layout global da aplicação.
