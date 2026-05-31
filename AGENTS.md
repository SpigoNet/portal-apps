# Portal Apps — Agentic Coding Guidelines

## Architecture
- **Stack:** Laravel 12 / PHP 8.2.
- **Structure:** Multi-module. All modules in `app/Modules/`. Each encapsulates an independent app sharing DB and auth.
- **Frontend:** Blade, Tailwind CSS, Alpine.js. **Livewire is PROHIBITED for new features.**
- **Database:** MySQL/MariaDB.

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

### ANT (Academic Notification Tool)
- **Purpose:** Academic management (Subjects, Professors, Assignments, Exams).
- **Prefix:** `/ant`
- **Key Note:** 3 profiles (Student, Professor, Admin). Integrates IA for correction suggestions.

### BolaoReuniao
- **Purpose:** Meeting prediction game.
- **Prefix:** `/bolao`
- **Key Note:** Models are global in `app/Models/` (not inside the module folder).

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

### StreamingManager
- **Purpose:** Shared streaming subscription management.
- **Prefix:** `/streaming-manager`
- **Key Note:** Fully public access (no auth required). Uses local layout.

### TreeTask
- **Purpose:** Project/Task management (Hierarchy: Project $\to$ Phase $\to$ Task).
- **Prefix:** `/treetask` (Web), `/treetask/api/v1` (REST API).
- **Key Note:** Custom PK names (e.g., `id_projeto`) instead of `id`. Full REST API documentation in `API_DOCUMENTATION.md`.

### VocabularioControlado
- **Purpose:** Controlled vocabulary management.
