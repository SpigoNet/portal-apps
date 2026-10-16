# Portal Apps — Agentic Coding Guidelines

## Architecture
- **Stack:** Laravel 12 / PHP 8.2.
- **Structure:** Multi-module. All modules in `app/Modules/`. Each encapsulates an independent app sharing DB and auth.
- **Frontend:** Blade, Tailwind CSS, Alpine.js. **Livewire is PROHIBITED for new features.**
- **Database:** PostgreSQL.

## Developer Commands
- **Frontend:** `npm run dev` / `npm run build`
- **Backend:** `php artisan serve`, `php artisan migrate`
- **Full Stack Dev:** `composer dev` (runs server, queue, logs, and vite concurrently)
- **Lint:** `./vendor/bin/pint` (Dry run: `./vendor/bin/pint --test`)
- **Test:** `php artisan test` (Specific: `php artisan test path/to/Test.php`)

## Conventions 
- **Naming:**
  - **Portuguese:** Business Entities/Models (e.g., `Tarefa`, `Contrato`). PascalCase. No module prefixes in class names.
  - **English:** Technical concepts (`Controller`, `Service`, `Provider`).
  - **camelCase:** Variables/Methods.
  - **snake_case:** DB columns.
  - **kebab-case:** Routes/Prefixes (e.g., `gestor-horas`).
  - **PascalCase:** View namespaces (e.g., `GestorHoras::index`).
- **Typing:** Strict type hints and return types for all methods, params, and properties. Use explicit nullables (`?string`).
- **Imports:** Sorted alphabetically; no unused imports.

## Rules
- **Modular SSR:** Controllers return Blade views via `view('Module::view')`. No REST APIs for UI.
- **Interactivity:** Use Alpine.js. Use traditional HTML form submissions with redirects and flash sessions.
- **Adding Modules:**
  1. Create `app/Modules/ModuleName/`.
  2. Create `ModuleNameServiceProvider.php` (load views, migrations, routes).
  3. Register provider in `bootstrap/providers.php`.
  4. Define routes in `routes.php` (wrapped in `web` and `auth` middleware, kebab-case prefix).
  5. Add `RegistrarAcesso:ModuleName` middleware for analytics.

## Modules Reference

### Admin
- **Purpose:** Central administration (Apps, Users, AI Providers/Models, Logs).
- **Prefix:** `/admin`
- **Key Note:** Manages global AI providers used by other modules.

### Alfred
- **Purpose:** Personal assistant / self-care dashboard (routines, medications, hydration, energy, TreeTask tasks).
- **Prefix:** `/alfred`
- **Key Note:** Self-contained layout with inline CSS (no Tailwind). WhatsApp notifications via Evolution API. See `app/Modules/Alfred/AGENTS.md`.

### ANT (Academic Notification Tool)
- **Purpose:** Academic management (Subjects, Professors, Assignments, Exams).
- **Prefix:** `/ant`
- **Key Note:** 3 profiles (Student, Professor, Admin). Integrates IA for correction suggestions.

### BolaoReuniao
- **Purpose:** Meeting prediction game.
- **Prefix:** `/bolao`
- **Key Note:** Models are global in `app/Models/` (not inside the module folder).

### Cardapio
- **Purpose:** Home cooking assistant: pantry stock with expiry, recipes, people/routine, restrictions and preferences → weekly menu and shopping list (deficit only).
- **Prefix:** `/cardapio`
- **Key Note:** All phases implemented (stock, recipes, weekly plan with auto-generation/optimisation, shopping list, history, leftovers, agenda, lunchbox, recurring products, natural-language assistant that only proposes validated actions for the user to confirm; progress in `PLANO.md`). A household has several `Pessoa`; a login is optional (`cardapio_pessoas.user_id` nullable), so kids or staff without an account still take part in menus and quantities. AI only interprets/explains; stock, restrictions and shopping are deterministic. See `app/Modules/Cardapio/AGENTS.md`.

### ComfyQueue
- **Purpose:** Queue management.

### DspaceForms
- **Purpose:** Visual editor for DSpace XML form configurations.
- **Prefix:** `/dspace-forms-editor`
- **Key Note:** Uses session-based configuration context (`DspaceConfigSession`).

### EnvioWhatsapp
- **Purpose:** Bulk WhatsApp message sender (3-step wizard).
- **Prefix:** `/ferramentas/whatsapp`
- **Key Note:** Stateless flow; no internal models.

### GestorHoras
- **Purpose:** Contract and hourly tracking management.
- **Prefix:** `/gestor-horas`
- **Key Note:** Supports public access via token for clients to view dashboards.

### Metricas
- **Purpose:** Access statistics.
- **Prefix:** `/metricas-sistema`
- **Key Note:** Provides the `RegistrarAcesso` middleware used by nearly all other modules.

### Mithril
- **Purpose:** Financial management (Accounts, Transactions).
- **Prefix:** `/mithril` (Web), `/api/mithril` (JSON API).
- **Key Note:** Distinguishes between "Pre-Transactions" (planned) and "Transactions" (effective).

### MundosDeMim
- **Purpose:** Daily AI Art generation based on biometric profiles.
- **Prefix:** `/mundos-de-mim`
- **Key Note:** Daily cron for generation; consumes AI via `AiProviderService`.

### QuebraCabeca
- **Purpose:** Classic rectangular-tile jigsaw: shuffled pieces are freely swapped until the image is rebuilt.
- **Prefix:** `/quebra-cabeca`
- **Key Note:** Auth-only. Users upload images (`Imagem` model, `public` disk, served via owner-checked route). Game logic is 100% Alpine.js in `index.blade.php`; best times live in `localStorage`. Default images are the files in `app/Modules/QuebraCabeca/imagens/` (drop a jpg/png/webp there, optionally naming it in `imagens/nomes.json`; served via the `padrao` route), plus the user's uploaded images.

### StreamingManager
- **Purpose:** Shared streaming subscription management.
- **Prefix:** `/streaming-manager`
- **Key Note:** Fully public access (no auth required). Uses local layout.

### TreeTask
- **Purpose:** Project/Task management (Hierarchy: Project $\to$ Phase $\to$ Task).
- **Prefix:** `/treetask` (Web), `/treetask/api/v1` (REST API).
- **Key Note:** Custom PK names (e.g., `id_projeto`) instead of `id`. Full REST API documentation in `API_DOCUMENTATION.md`.

### Terminal
- **Purpose:** Bus-terminal puzzle presented as an old CRT computer: fill each bus in the right order before the parking slots run out.
- **Prefix:** `/terminal`
- **Key Note:** Auth-only, no models/DB beyond the `PortalApp` row. Game logic 100% Alpine.js in `index.blade.php`; progress/phosphor/mute in `localStorage`. PWA fullscreen + own icon. See `app/Modules/Terminal/AGENTS.md`.

### VocabularioControlado
- **Purpose:** Controlled vocabulary management.

### Yomi
- **Purpose:** Personal manga catalog, reading progress and metadata sync (Jikan + AniList).
- **Prefix:** `/yomi`
- **Key Note:** Jikan is the primary provider, AniList the fallback; local DB (PostgreSQL) is the primary source. Media mirrored locally with checksum-based deduplication. Tests use `RefreshYomiDatabase` trait (full migration chain has SGBD-specific statements incompatible with sqlite; the trait runs only `users` + `yomi_*`). See `app/Modules/Yomi/AGENTS.md`.
