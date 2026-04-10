# Portal Apps — Agentic Coding Guidelines (AGENTS.md)

This document is the source of truth for AI agents and developers operating in the Portal Apps repository. It contains essential context, architectural rules, code style guidelines, and commands.

## 1. System Overview & Architecture
- **Structure:** Laravel multi-module application. All modules live in `app/Modules/`. Each module encapsulates an independent application sharing the same database and authentication.
- **Backend:** PHP 8+ / Laravel 11.
- **Frontend:** Blade, Tailwind CSS, Alpine.js (Livewire is PROHIBITED for new features).
- **Database:** MySQL/MariaDB with Eloquent ORM.

## 2. CLI Commands: Build, Lint, and Test

### Building & Running
- **Frontend Assets (Dev):** `npm run dev`
- **Frontend Assets (Prod):** `npm run build`
- **Serve Application:** `php artisan serve`
- **Migrate DB:** `php artisan migrate`

### Linting & Formatting
The project uses **Laravel Pint** for PHP code style enforcement.
- **Run Linter/Formatter:** `./vendor/bin/pint`
- **Test Linter (Dry Run):** `./vendor/bin/pint --test`

### Testing (PHPUnit)
Always write tests for new features and bug fixes.
- **Run all tests:** `php artisan test`
- **Run a specific test file:** `php artisan test tests/Feature/MyTest.php`
- **Run a single test method:** `php artisan test --filter test_method_name`
- **Run module-specific tests:** `php artisan test app/Modules/ModuleName/tests/`

## 3. Code Style & Conventions

### 3.1 Naming Conventions
- **Business Entities/Models:** Use Portuguese (e.g., `Tarefa`, `Contrato`). PascalCase for class names. Models specific to a module sit in `app/Modules/NomeDoModulo/Models/` without module prefixes unless necessary (e.g. `Contrato`, not `GestorHorasContrato`).
- **Technical Concepts:** Use English (e.g., `Controller`, `Service`, `Provider`).
- **Variables/Methods:** camelCase (e.g., `$dadosUsuario`, `calcularTotal()`).
- **Database Columns:** snake_case (e.g., `data_inicio`, `usuario_id`).
- **Routes & Prefix Names:** kebab-case (e.g., `gestor-horas`, `mundos-de-mim`).
- **View Namespaces:** PascalCase (e.g., `GestorHoras::index`).

### 3.2 Types & Signatures
- **Strict Typing:** Always use strong type hints and return types for methods, parameters, and properties (PHP 8+ features).
- **Nullables:** Explicitly define nullable types (e.g., `?string $name`).
- **DocBlocks:** Keep DocBlocks minimal. Rely on native PHP type hints instead of `@param` or `@return` tags whenever possible.

### 3.3 Imports (Use Statements)
- Sort imports alphabetically.
- Do not leave unused imports.
- Import standard Facades and Eloquent models at the top of the file rather than using fully qualified class names inline.

### 3.4 Error Handling & Validation
- **Validation:** Always validate request data using Laravel Form Requests (`app/Http/Requests/`) or `$request->validate()` before processing.
- **Exceptions:** Throw custom or descriptive exceptions for edge cases. Avoid generic catch-all `\Exception` blocks unless necessary.
- **Transactions:** Use `DB::transaction()` when creating or updating multiple related models to ensure database integrity.

## 4. Architectural Rules & Best Practices

### 4.1 Modular Server-Side Rendering (SSR)
- **MVC Pattern:** Controllers receive requests, interact with Models, and return Blade Views using `compact()` or `with()`.
- **No REST for UI:** Do not expose data via JSON REST APIs for the frontend unless the module explicitly requires an external API (like TreeTask).
- **Views:** Controller returns must reference the module's namespace: `return view('NomeDoModulo::index', compact('dados'));`.

### 4.2 Interactive Elements
- **No Livewire:** Livewire is strictly banned for new functionalities.
- **Use Alpine.js:** For client-side interactivity, use Alpine.js.
- **Form Submissions:** Rely on traditional HTML form submissions handling state via Laravel standard redirects and flash sessions (`session('success')`).

### 4.3 Adding New Modules
1. **Directory:** Create `app/Modules/ModuleName/`.
2. **Provider:** Create `ModuleNameServiceProvider.php` to load views (`resources/views`), migrations, and routes.
3. **Register:** Add the provider to `bootstrap/providers.php`.
4. **Routes:** Define web routes in `routes.php` wrapped in the `web` and `auth` middleware, prefixed with kebab-case module name.
5. **Metrics:** If the module needs analytics, add the `RegistrarAcesso:ModuleName` middleware to its routes.
