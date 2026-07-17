# Alfred Module — Agent Guidelines

## Purpose

Personal assistant / self-care dashboard. Tracks routines, medications, water intake, energy levels, and tasks (from TreeTask). Includes a "Dia Ruim" (bad day) mode that simplifies the UI to breathing exercises, hydration, and rest.

**Prefix:** `/alfred` | **Auth:** required | **Analytics:** `RegistrarAcesso:Alfred`

## Critical Gotchas

### Layout is NOT Tailwind/Alpine
Alfred uses a **fully self-contained layout** (`resources/views/layouts/app.blade.php`) with inline CSS and vanilla JS. This is intentionally different from the rest of the portal-apps codebase. Do not refactor it to use Tailwind/Alpine.

### Custom Route Binding — User Scoping
Both `Rotina` and `Medicamento` override `resolveRouteBinding()` to scope queries to `auth()->id()`. Route model binding automatically filters by user. Do not add manual `where('user_id', ...)` checks when using these models via route params.

### Hardcoded "chopper" Persona
`MedicamentoController@tomar` and `HidratacaoController@store` hardcode `Persona::where('slug', 'chopper')` for WhatsApp messages. If the persona doesn't exist, messages silently fail (try/catch swallows exceptions). This is by design — WhatsApp is optional.

### Domain Logic Lives in Models
`Rotina` and `RegistroMedicamento` contain substantial business logic (recurrence evaluation, stock management, "today" queries). Do not extract this to services unless there's a concrete reason.

### Undo Pattern
Nearly every action has an undo counterpart: `marcarExecutada` → `desfazerExecucao`, `pularHoje` → `desfazerPulo`, `tomar` → `desfazer`. Maintain this symmetry when adding new actions.

### RotinaCategoria Joins via Slug
`RotinaCategoria::rotinas()` uses a non-standard relationship: `'categoria', 'slug'` (FK on Rotina references the slug column, not a PK). If you add category-related queries, use this relationship instead of manual joins.

### `RotinaExecucao.user_id` is Dead Schema
The migration creates `user_id` on `alfred_rotina_execucoes` but the model does not include it in `$fillable` and nothing populates it. Ignore this column.

### Route Order Matters
The `calendario` route (`/rotinas/calendario/{visualizacao?}`) is registered **before** `resource('rotinas', ...)`. This prevents the resource route from capturing `calendario` as an ID. Preserve this order.

## Cross-Module Dependencies

### Imports
- `App\Modules\TreeTask\Models\Tarefa` — used in `DashboardController`, `TarefaController`, and `RelatorioManha` command. TreeTask queries are wrapped in try/catch for graceful degradation.

### Global User Model
The `User` model (`app/Models/User.php`) has 5 direct relationships to Alfred models (`hasOne(UserProfile)`, `hasMany(Medicamento)`, etc.). Changes to Alfred model table names affect the global User model.

### External Route References
- `route('login')` — from `auth/login.blade.php`
- `route('treetask.index')` — from `tarefas/index.blade.php`
- `route('push.test-now')`, `route('push.test-delayed')`, `/push/subscribe` — from `rotinas/manage.blade.php` (defined outside this module)
- `config('webpush.vapid.public_key')` — from `rotinas/manage.blade.php`

## Database

**9 migrations, 9 tables** — all prefixed `alfred_`. Key tables:
- `alfred_rotinas` + `alfred_rotina_execucoes` + `alfred_rotina_pulos` — routine tracking with recurrence
- `alfred_rotina_categorias` — seeded with 7 default categories on migration
- `alfred_medicamentos` + `alfred_registro_medicamentos` — medication inventory + dose log
- `alfred_consumo_agua` — water intake log
- `alfred_user_profiles` — per-user settings (water goal, energy, TreeTask credentials, dia-ruim flag)
- `alfred_logs_dia_ruim` — bad day mode activation log
- `alfred_personas` — WhatsApp persona config (used with Evolution API)

## Console Commands

- `alfred:relatorio-manha` — generates a morning report per user (TreeTask top-3 tasks, medication alerts, hydration status). Outputs to console only. Skips users with dia-ruim active.

## WhatsApp Integration

`EvolutionApiService` sends messages to WhatsApp groups via Evolution API (local network: `config('services.evolution.base_uri')`, default `http://192.168.15.10:8099`). Used only for medication and hydration notifications through the "chopper" persona.

## Views

20 Blade views. The login page (`auth/login.blade.php`) is standalone — does not extend the Alfred layout. All other views extend `Alfred::layouts.app`.

## Testing

No test files exist in this module yet. When adding tests, place them in `tests/Modules/Alfred/` following the project-wide test conventions.
