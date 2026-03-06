# Módulo Metricas — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **Metricas** registra e exibe estatísticas de acesso ao sistema. Ele fornece:

- Contagem de acessos por módulo
- Lista dos 50 acessos mais recentes com detalhes do usuário
- Ranking dos 10 usuários mais ativos

Além disso, este módulo fornece o middleware **`RegistrarAcesso`** que é utilizado por **todos os outros módulos** para registrar os acessos automaticamente.

**Acesso:** Somente administradores (`middleware: admin`).

---

## 2. Estrutura de Diretórios

```
app/Modules/Metricas/
├── Http/
│   └── Controllers/
│       └── MetricasController.php         # Dashboard de métricas
├── Middleware/
│   └── RegistrarAcesso.php                # ← Usado por outros módulos
├── Models/
│   └── MetricaAcesso.php                  # Registro de acesso
├── resources/
│   └── views/
│       └── index.blade.php                # Dashboard de métricas
├── MetricasServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/metricas-sistema`  
**Middleware:** `web`, `auth`, `admin`  
**Nome base:** `metricas.*`

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/metricas-sistema` | `metricas.index` | Dashboard de métricas |

---

## 4. Controllers

### `MetricasController`

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET `/metricas-sistema` | Agrega e exibe dados de acesso |

**Dados exibidos:**
- Total de acessos por módulo (agrupado por `modulo_nome`)
- Últimos 50 acessos com `user_id`, `modulo_nome`, `created_at`
- Top 10 usuários por quantidade de acessos

---

## 5. Middleware `RegistrarAcesso`

**Localização:** `app/Modules/Metricas/Middleware/RegistrarAcesso.php`

Este middleware é o coração do sistema de métricas. Ele deve ser adicionado às rotas de **qualquer módulo** que precise aparecer nas estatísticas:

```php
Route::middleware(['web', 'auth', 'RegistrarAcesso:NomeDoModulo'])
    ->prefix('meu-modulo')
    ->group(function () { ... });
```

O parâmetro `NomeDoModulo` é o nome que aparecerá no dashboard de métricas.

**Módulos que já usam `RegistrarAcesso`:**
- `ANT`
- `DspaceForms`
- `EnvioWhatsapp`
- `GestorHoras`
- `Mithril`
- `TreeTask`

---

## 6. Model

### `MetricaAcesso`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` (usuário que acessou) |
| `modulo_nome` | string | Nome do módulo acessado |
| `created_at` | timestamp | Data/hora do acesso |

---

## 7. Views

### `index.blade.php`

Dashboard exibindo:
1. **Card por módulo:** Nome do módulo + total de acessos
2. **Tabela de acessos recentes:** Usuário, módulo, data/hora
3. **Ranking de usuários:** Top 10 usuários por quantidade de acessos

---

## 8. Como Registrar Métricas em um Novo Módulo

1. No arquivo `routes.php` do novo módulo, adicione o middleware `RegistrarAcesso`:

```php
Route::middleware(['web', 'auth', 'RegistrarAcesso:NomeDoMeuModulo'])
    ->prefix('meu-modulo')
    ->name('meu-modulo.')
    ->group(function () {
        // suas rotas
    });
```

2. O middleware cria um registro em `MetricaAcesso` automaticamente a cada requisição.

---

## 9. Notas para Agentes de IA

- O middleware `RegistrarAcesso` está localizado **dentro** do módulo Metricas, mas é usado por módulos externos.
- O módulo não possui models de negócio — apenas o model de rastreamento `MetricaAcesso`.
- Acesso ao dashboard restrito a `admin` (via middleware `admin`, que verifica `EnsureUserIsAdmin`).
- Para visualizar as métricas de um módulo específico, filtre por `modulo_nome` na tabela `metrica_acessos`.
