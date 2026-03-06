# Portal Apps — Guia para Desenvolvedores e Agentes de IA

Este documento é o ponto de entrada para qualquer desenvolvedor ou agente de IA que precise entender, modificar ou expandir este repositório.

---

## 1. Visão Geral do Projeto

**Portal Apps** é uma plataforma Laravel multi-módulo. Cada módulo encapsula um aplicativo ou ferramenta independente, acessível através de um portal central. Os módulos compartilham a mesma base de dados, autenticação e layout, mas têm sua própria lógica de negócios, rotas, controllers, models e views.

**Stack principal:**
- **Backend:** PHP 8+ / Laravel 11
- **Frontend:** Blade + Tailwind CSS + Alpine.js (mínimo JS)
- **Banco de dados:** MySQL/MariaDB (Eloquent ORM)
- **Autenticação:** Laravel Fortify + Google OAuth (Socialite)
- **Build:** Vite + PostCSS

---

## 2. Estrutura do Repositório

```
portal-apps/
├── app/
│   ├── Console/            # Artisan commands globais
│   ├── Helpers/            # Funções helper globais
│   ├── Http/               # Controllers, Middleware e Requests globais
│   ├── Livewire/           # Componentes Livewire (autenticação)
│   ├── Models/             # Models globais (User, PortalApp, etc.)
│   ├── Modules/            # ← TODOS os módulos ficam aqui
│   │   ├── Admin/
│   │   ├── ANT/
│   │   ├── BolaoReuniao/
│   │   ├── DspaceForms/
│   │   ├── EnvioWhatsapp/
│   │   ├── GestorHoras/
│   │   ├── Metricas/
│   │   ├── Mithril/
│   │   ├── MundosDeMim/
│   │   ├── StreamingManager/
│   │   └── TreeTask/
│   ├── Providers/          # Service Providers globais
│   ├── Services/           # Serviços globais (ex: AI)
│   └── Support/            # Classes de suporte
├── bootstrap/
│   └── providers.php       # ← Registrar novos módulos aqui
├── config/                 # Configurações do Laravel
├── database/               # Migrations e seeders globais
├── public/                 # Assets públicos
├── resources/
│   └── views/              # Layouts globais e componentes Blade
├── routes/
│   ├── web.php             # Rotas globais (home, auth, etc.)
│   └── auth.php            # Rotas de autenticação
└── tests/                  # Testes automatizados
```

---

## 3. Princípios de Arquitetura

### 3.1 Modularidade

Cada funcionalidade vive dentro do seu módulo em `app/Modules/`. Os módulos são independentes entre si (exceto Models globais compartilhados). Cada módulo tem seu próprio `ServiceProvider` que registra rotas e views.

### 3.2 Padrão MVC Server-Side (SSR)

A aplicação segue o padrão MVC clássico com renderização no servidor:

- **Controllers** recebem requisições HTTP, usam **Models** (Eloquent) e retornam **Views** (Blade).
- **Não exponha dados via API REST** para o frontend (exceto módulos que possuem API dedicada como TreeTask).
- Dados passam do controller para a view via `compact()` ou `with()`.

```php
// ✅ Correto
return view('MeuModulo::index', compact('dados'));

// ❌ Errado para fluxo normal
return response()->json($dados);
```

### 3.3 Sem Livewire nas Funcionalidades

Livewire é **proibido** para novas funcionalidades. Use:
- Formulários HTML tradicionais com submissão de página
- Alpine.js para interatividade mínima no cliente
- JavaScript vanilla quando estritamente necessário

### 3.4 Localização dos Models

Models específicos de um módulo ficam **dentro do módulo**:
```
app/Modules/MeuModulo/Models/MeuModel.php
```

Models compartilhados entre módulos ficam em:
```
app/Models/
```

---

## 4. Mapa dos Módulos

| Módulo | URL Prefix | Propósito | Acesso |
|--------|-----------|-----------|--------|
| [Admin](app/Modules/Admin/AGENTS.md) | `/admin` | Administração do portal (apps, usuários, IA, logs) | Somente admin |
| [ANT](app/Modules/ANT/AGENTS.md) | `/ant` | Sistema acadêmico (matérias, trabalhos, provas, correção com IA) | Auth |
| [BolaoReuniao](app/Modules/BolaoReuniao/AGENTS.md) | `/bolao` | Bolão de previsões para reuniões | Público |
| [DspaceForms](app/Modules/DspaceForms/AGENTS.md) | `/dspace-forms-editor` | Editor visual de formulários DSpace (XML) | Auth |
| [EnvioWhatsapp](app/Modules/EnvioWhatsapp/AGENTS.md) | `/ferramentas/whatsapp` | Envio em massa de WhatsApp | Auth |
| [GestorHoras](app/Modules/GestorHoras/AGENTS.md) | `/gestor-horas` | Gestão de contratos e apontamentos de horas | Auth + Token público |
| [Metricas](app/Modules/Metricas/AGENTS.md) | `/metricas-sistema` | Dashboard de métricas de acesso ao sistema | Somente admin |
| [Mithril](app/Modules/Mithril/AGENTS.md) | `/mithril` | Gestão financeira pessoal (contas, transações, pré-transações) | Auth |
| [MundosDeMim](app/Modules/MundosDeMim/AGENTS.md) | `/mundos-de-mim` | Geração diária de arte personalizada por IA | Público (landing) + Auth |
| [StreamingManager](app/Modules/StreamingManager/AGENTS.md) | `/streaming-manager` | Gerenciador de assinaturas de streaming compartilhadas | Público |
| [TreeTask](app/Modules/TreeTask/AGENTS.md) | `/treetask` | Gestão de projetos e tarefas com IA, gamificação e API REST | Auth + API Token |

---

## 5. Como Criar um Novo Módulo

Consulte o [README.md](README.md) para o guia completo. Resumo rápido:

### Passo 1: Estrutura de pastas

```
app/Modules/NomeDoModulo/
├── database/
│   └── migrations/
├── Http/
│   └── Controllers/
├── Models/
├── resources/
│   └── views/
├── routes.php
└── NomeDoModuloServiceProvider.php
```

### Passo 2: Service Provider

```php
<?php
namespace App\Modules\NomeDoModulo;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class NomeDoModuloServiceProvider extends ServiceProvider
{
    protected $namespace = 'NomeDoModulo';

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        Route::middleware('web')->group(__DIR__ . '/routes.php');
    }
}
```

### Passo 3: Registrar em `bootstrap/providers.php`

```php
App\Modules\NomeDoModulo\NomeDoModuloServiceProvider::class,
```

### Passo 4: Definir rotas em `routes.php`

```php
Route::middleware(['web', 'auth'])
    ->prefix('nome-do-modulo')
    ->name('nome-do-modulo.')
    ->group(function () {
        Route::get('/', [MeuController::class, 'index'])->name('index');
    });
```

### Passo 5: Controllers e Views

Controllers devem sempre retornar `view()` ou `redirect()`. Views ficam em `resources/views/` do módulo e são referenciadas pelo namespace: `NomeDoModulo::index`.

---

## 6. Autenticação e Autorização

### Autenticação

- Baseada em sessão Laravel (Fortify)
- Login via formulário ou **Google OAuth** (`/google/redirect`, `/google/callback`)
- Middleware `auth` protege rotas autenticadas

### Roles e Gates

Existem dois níveis de acesso elevado:

1. **Admin do Portal:** Middleware `admin` (checar `EnsureUserIsAdmin` em `app/Http/Middleware`). Usado pelo módulo Admin e Metricas.

2. **Admin do App:** Gate `admin-do-app` definido dentro do módulo (ex: ANT, MundosDeMim). Baseado no `portal_app_id` do módulo.

```php
// Verificar admin do app no controller
$this->authorize('admin-do-app');
// ou
Gate::allows('admin-do-app')
```

### Acesso por Token (API)

O módulo TreeTask possui autenticação por token para a API REST:
- Header `X-User-ID`: ID do usuário
- Header `X-Token`: `md5(email + password_hash)`
- Middleware: `TokenAuth`

---

## 7. Middleware Global Importante

| Middleware | Localização | Função |
|-----------|-------------|--------|
| `auth` | Laravel built-in | Requer login |
| `admin` | `app/Http/Middleware/EnsureUserIsAdmin.php` | Requer role admin |
| `RegistrarAcesso:NomeModulo` | `app/Modules/Metricas/Middleware/RegistrarAcesso.php` | Registra acesso para métricas |
| `TokenAuth` | `app/Modules/TreeTask/Http/Middleware/TokenAuth.php` | Autenticação por token API |

O middleware `RegistrarAcesso` deve ser adicionado às rotas de novos módulos para aparecer nas métricas do sistema:
```php
Route::middleware(['web', 'auth', 'RegistrarAcesso:NomeDoModulo'])
```

---

## 8. Models Globais Importantes

Localizados em `app/Models/`:

| Model | Tabela | Descrição |
|-------|--------|-----------|
| `User` | `users` | Usuário do sistema |
| `PortalApp` | `portal_apps` | Aplicativos/módulos registrados no portal |

O model `PortalApp` representa a entrada de um módulo no portal (título, ícone, link, visibilidade).

---

## 9. Padrões de Views

### Layout Base

Use o componente `<x-app-layout>` para páginas autenticadas:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2>Título da Página</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- conteúdo --}}
        </div>
    </div>
</x-app-layout>
```

### Mensagens Flash

```blade
@if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        {{ session('success') }}
    </div>
@endif
```

### Referência de Views do Módulo

Views são referenciadas pelo namespace do módulo:
```php
return view('NomeDoModulo::index', compact('dados'));
return view('NomeDoModulo::subpasta.arquivo', compact('dados'));
```

---

## 10. Rotas Globais

| Rota | Controller | Descrição |
|------|-----------|-----------|
| `GET /` | `WelcomeController@index` | Página inicial do portal |
| `GET /profile` | (view direta) | Perfil do usuário |
| `GET /google/redirect` | `GoogleController@redirect` | Início OAuth Google |
| `GET /google/callback` | `GoogleController@callback` | Callback OAuth Google |
| `GET /manifest/{id}/manifest.json` | `ManifestController@show` | PWA manifest por app |
| `GET /run-migrations` | closure (auth) | Executa migrations forçadas |

---

## 11. Como Rodar o Projeto

```bash
# Instalar dependências
composer install
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Banco de dados
php artisan migrate

# Compilar assets
npm run dev   # desenvolvimento
npm run build # produção

# Servidor de desenvolvimento
php artisan serve
```

---

## 12. Testes

```bash
php artisan test
# ou
./vendor/bin/phpunit
```

Configuração em `phpunit.xml`.

---

## 13. Integração com IA

Vários módulos utilizam serviços de IA:

- **Admin:** Configura provedores de IA (OpenAI, Anthropic, etc.) e modelos padrão. Modelos configurados aqui ficam disponíveis para toda a aplicação.
- **ANT:** Sugestões de correção de trabalhos via IA.
- **TreeTask:** Geração de tarefas via IA, mensagens motivacionais.
- **MundosDeMim:** Geração diária de imagens personalizadas por IA.

A camada de abstração de IA está em `app/Services/` e `app/Models/AIProvedor.php` / `AIModelo.php`.

---

## 14. Convenções de Código

- **Nomenclatura:** Português para entidades de negócio (ex: `Tarefa`, `Contrato`, `Apontamento`). Inglês para conceitos técnicos do Laravel (Controller, Service, Provider).
- **Namespaces:** `App\Modules\NomeDoModulo\...`
- **Prefixo de rotas:** kebab-case do nome do módulo (ex: `gestor-horas`, `mundos-de-mim`)
- **Namespace de views:** PascalCase (ex: `GestorHoras`, `MundosDeMim`)
- **Models do módulo:** PascalCase sem prefixo de módulo quando possível (ex: `Contrato`, não `GestorHorasContrato`), a não ser que haja conflito.
