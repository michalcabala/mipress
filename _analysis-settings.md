# Blueprint-driven Settings — Analysis

## Aktualizace stavu k 10. dubna 2026

Tento dokument vznikl jako návrh cílového řešení. K dnešnímu stavu už jsou hlavní části settings architektury implementované:

- Migrace `2026_04_06_120000_restructure_settings_table_for_blueprints.php` už převádí původní key-value `settings` tabulku na strukturu `id`, `handle`, `name`, `blueprint_id`, `data`, `icon`, `sort_order`.
- `SettingsManager` už existuje a načítá settings z nové tabulky včetně vazby na Blueprint.
- Filament stránka `EditSettings` už existuje a renderuje settings formuláře dynamicky přes `BlueprintFieldResolver`.
- Helper `settings()` je primární přístupová cesta; `global_set()` je nyní jen kompatibilní wrapper delegující na settings.
- `GlobalSeoSettings` a `GlobalSeoSettingsManager` zůstávají jako specializovaná SEO vrstva vedle obecných Blueprint-driven settings.
- `GlobalSetResource` je v kódu stále přítomen, takže dřívější plán na úplné odstranění legacy global sets je jen částečně dokončený.

## 1) Blueprint model and `fields` structure

File: `packages/mipress/core/src/Models/Blueprint.php`

- Table: `blueprints`
- Fillable: `name`, `handle`, `fields`
- Casts: `fields => array`
- Relationships:
  - `collections(): HasMany`
  - `entries(): HasMany`

Observed `fields` JSON shapes in current seed data:

- Nested sections shape (already used in app):
  - `[{ section: "Obsah", fields: [{ handle, label, type, required?, config? }, ...] }]`
- Flat shape is also supported by resolver with optional `order` field.

## 2) How EntryResource renders dynamic Blueprint fields

Files:
- `packages/mipress/core/src/Filament/Resources/EntryResource/Schemas/EntryForm.php`
- `packages/mipress/core/src/Services/BlueprintFieldResolver.php`

Current mechanism:

- `EntryForm` resolves current collection and its blueprint.
- Dynamic fields are rendered via:
  - `...BlueprintFieldResolver::resolveAll($blueprint->fields ?? [])`
- `BlueprintFieldResolver` already supports:
  - flat array fields and nested section->fields arrays
  - mapping type -> Filament component (`text`, `textarea`, `select`, `media`, `repeater`, `mason`, etc.)
  - `statePath('data')` for resolved sections

Conclusion:
- Mapping logic is already extracted in shared service and can be reused for Settings page without duplication.

## 3) Existing migrations for settings/globals

Files:
- `packages/mipress/core/database/migrations/2026_03_29_200000_create_settings_table.php`
- `packages/mipress/core/database/migrations/2026_03_29_300000_create_global_sets_table.php`

Current state:

- `settings` table exists as legacy key-value:
  - `key` (primary string), `value` (text nullable), timestamps
- `global_sets` table exists:
  - `id`, `handle` (unique), `title`, `data` (json), timestamps

Conclusion:
- Existing `settings` schema does not match requested Blueprint-driven group settings.
- Existing `global_sets` represents the old concept and should be replaced in admin flow.

## 4) Navigation registration

Files:
- `packages/mipress/core/src/Filament/MiPressPlugin.php`
- `app/Providers/Filament/AdminPanelProvider.php`

Current state:

- Core plugin registers navigation groups including `Nastavení`.
- Resources registered explicitly, including `GlobalSetResource`.
- Standalone pages (`ThemeSettings`, `SitemapSettings`) are listed in plugin `->pages([...])`.

Conclusion:
- Dynamic Settings navigation can be implemented as Filament Page with custom `getNavigationItems()` and grouped under `Nastavení`.

## 5) Related legacy components to migrate from

- Legacy key-value model: `packages/mipress/core/src/Models/Setting.php`
- Legacy global manager: `packages/mipress/core/src/Services/GlobalSetManager.php`
- Legacy helper: `global_set()` in `packages/mipress/core/src/helpers.php`
- Legacy resource + policy:
  - `GlobalSetResource`
  - `GlobalSetPolicy`
- Permissions currently include `global_set.*`, but no `settings.manage`.

## 6) Constraints discovered

- `UserRole` enum currently contains: `SuperAdmin`, `Admin`, `Editor`, `Contributor` (no `Author` case in current codebase).
- Package tables currently do not use `mipress_` prefix (despite guideline note).
- Existing codebase widely uses `declare(strict_types=1)` in package classes.

## 7) Implementation impact summary

Planned changes based on current codebase:

- Replace legacy `settings` schema via new migration to grouped JSON settings with blueprint FK.
- Introduce new `Setting` model behavior (`handle`, `name`, `blueprint_id`, `data`, `icon`, `sort_order`, `get/set`).
- Add `SettingsManager` service and `settings()` helper.
- Replace global view composer with `mipress::*` scoped settings composer.
- Add universal `EditSettings` Filament page with route param `{handle}`, dynamic schema via `BlueprintFieldResolver`.
- Remove `GlobalSetResource` from navigation/registration flow.
- Add `settings.manage` permission to seeders and guard page access by role+permission.
- Add skeleton `SettingsSeeder` and invoke from `DatabaseSeeder`.
- Add Pest tests for model, manager, helper, page access/save behavior, and authorization.
