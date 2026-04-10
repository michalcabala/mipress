# miPress — Copilot Instructions

## Project Overview

miPress is a modular CMS built primarily as a **set of Laravel packages** (`mipress/core`, `mipress/forms`, `mipress/social-feeds`) installed via Composer into a Laravel skeleton project. It is NOT a standalone package-only repository — reusable domain code lives primarily in `packages/`, while the Laravel skeleton in `app/` wires panel, provider, and host-app integration.

## Tech Stack

- **PHP 8.3+** with strict types, readonly properties, enums, named arguments, and PHP 8 attributes where Laravel 13 supports them
- **Laravel 13** — use current L13 conventions (e.g. `#[Table]`, `#[Fillable]` attributes are optional but preferred for new code)
- **Filament 5** — admin panel at `/mpcp`, panel ID `admin`
- **Livewire 4** — for interactive frontend components
- **Blade** — templating engine, no Inertia/Vue/React
- **Tailwind CSS 4** — for frontend styling
- **Pest PHP** — for all tests, never PHPUnit syntax

## Core Packages

- `spatie/laravel-permission` — roles & permissions via `UserRole` enum (SuperAdmin, Admin, Editor, Contributor), NOT Filament Shield
- `spatie/laravel-translatable` — model translations stored as JSON, accessed via custom `TranslatableField` helper with Filament Tabs and dot notation
- `awcodes/filament-curator` — media management, NOT Spatie Media Library
- `awcodes/mason` — structured content editing for all Entry content, NO free-form rich text editors (TinyMCE, CKEditor, Trix)
- `spatie/laravel-sluggable` — automatic slug generation
- `blade-ui-kit/blade-icons` — icon sets with custom FA Light and FA Brands sets

## Package Development Rules

- This is a package-driven Laravel project, not a standalone package-only repository.
- Core package classes belong under the `MiPress\Core` namespace. Other packages use their own namespaces such as `MiPress\Forms`.
- The ServiceProvider is `MiPress\Core\MiPressServiceProvider`.
- Migrations are loaded from the package's `database/migrations/` directory via `loadMigrationsFrom()`.
- Config is published from `config/mipress.php`.
- Views are registered under the `mipress` namespace: `view('mipress::template.name')`.
- Routes are loaded from the package's `routes/` directory.
- Package Filament Resources, Pages, and Widgets are auto-discovered from package `src/Filament/` directories, while the host app may also register `app/Filament/*` classes.

## Coding Conventions

- Use typed properties, return types, and parameter types everywhere — no mixed types unless truly necessary.
- Prefer `readonly` properties and constructor promotion where possible.
- Use enums instead of string/int constants.
- Use `match` expressions instead of `switch` statements.
- Use early returns to reduce nesting.
- Use Laravel's `str()` and `arr()` helpers, not raw PHP string/array functions.
- Method names: camelCase. Properties: camelCase. Database columns: snake_case.
- Prefer collection methods (`->map()`, `->filter()`) over `foreach` loops.
- No `@author` or `@copyright` docblocks. Use PHPDoc only for complex parameter types or when IDE needs help.

## Eloquent Models

- Always define `$fillable` (or use `#[Fillable]` attribute in L13).
- Always define `$casts` for dates, enums, booleans, and JSON fields.
- Always define relationships with return types: `return type: HasMany`, `BelongsTo`, etc.
- Use model factories for all models.
- Prefer query scopes over raw where clauses in controllers/services.
- Use `SoftDeletes` trait on content models (Entry, Collection, Taxonomy, Term).

## Filament 5 Conventions

- All Resources go in `src/Filament/Resources/`.
- All Pages go in `src/Filament/Pages/`.
- All Widgets go in `src/Filament/Widgets/`.
- Use Filament's built-in form components — do NOT create custom components unless absolutely necessary.
- Use `Section`, `Grid`, and `Tabs` for form layout.
- Use Curator's `CuratorPicker` for all image/file fields, never `FileUpload`.
- Use Mason's `Mason` field for the base content editor on Entries and Pages. In Entries it is stored at `data.content`; Blueprint fields are additional custom fields rendered alongside Mason.
- Blueprint field types are registered through `FieldTypeRegistry` and rendered via `BlueprintFieldResolver`; extend that system instead of adding ad-hoc type switches in forms or tables.
- Blueprint field definitions may include table-display settings such as `show_in_table`, `searchable`, and `sortable`, which drive dynamic Entry table columns and filters.
- Blueprint custom field definitions may use `RichEditor` when the user selects the `richtext` field type — this is intentional for simple inline fields inside Blueprint-driven forms.
- Translatable fields use custom `TranslatableField::make()` helper that wraps content in Filament Tabs with locale flags.
- Use Font Awesome icon identifiers everywhere (`fal-*`, `far-*`, `fas-*`, `fab-*`). Do not use Heroicons in new or updated code.
- Labels and navigation in Czech: Sekce (Collection), Šablona (Template), Třídění (Taxonomy), Štítek (Term), Položka (Entry).
- If breadcrumbs should be hidden only on index tables, override `getBreadcrumbs(): array` on the specific `ListRecords` page instead of disabling breadcrumbs globally in the panel provider.

## Testing

- Use **Pest PHP** exclusively. Never use PHPUnit class-based syntax.
- Test files go in `tests/` with `Feature/` and `Unit/` subdirectories.
- Use `it('does something')` syntax, not `test('does something')`.
- Use Pest's `expect()` API for assertions, not `$this->assert*()`.
- Run `php artisan test` after implementing each feature.
- Use Laravel model factories for test data.

## Laravel Boost

- **Always use Laravel Boost** (`mcp_laravel-boost_search-docs`) to verify API before writing any Filament, Livewire, or Laravel code.
- Pass relevant `packages` array to scope results (e.g. `["filament/filament"]`, `["awcodes/mason"]`).
- Use multiple broad queries per search call.
- Do not skip this step — always search docs before implementing.

## Git & Commits

- Write all commit messages in **English**.
- Use **Conventional Commits** format: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`.
- Keep commits atomic — one logical change per commit.

## Artisan Commands

- After creating models, always run `php artisan make:factory ModelNameFactory`.
- After creating migrations, verify with `php artisan migrate --pretend`.
- After modifying Filament resources, clear caches: `php artisan filament:cache-components`.

## Czech Language Context

- This CMS targets Czech small business websites.
- Admin UI labels are in Czech (see Filament conventions above).
- Frontend content is primarily in Czech with optional multilingual support.
- URL slugs are generated with Czech diacritics removed (háčky, čárky).
- Date/time formatting uses Czech locale (`cs_CZ`).

## What NOT To Do

- Do NOT delete any data in the database.
- Do NOT use `dd()` or `dump()` in committed code — use `Log::debug()` for debugging.
- Do NOT use raw SQL queries — always use Eloquent or Query Builder.
- Do NOT install packages without confirming with the developer first.
- Do NOT create separate CSS/JS files — styles go into Tailwind classes, scripts into Blade/Livewire.
- Do NOT use `env()` outside of config files.
- Do NOT use Filament Shield — we use Spatie Permission directly with UserRole enum.
- Do NOT use RichEditor or WYSIWYG for the main Entry content — that must use Mason. In Blueprint field definitions, `RichEditor` is allowed when the field type is `richtext`.
