---
applyTo: "packages/mipress/**"
---

# miPress Canonical Instructions

Tento soubor je hlavní zdroj pravdy pro projektové instrukce.

Path-specific wrappery (`filament.instructions.md`, `models.instructions.md`, `tests.instructions.md`) mají jen doplňující pravidla a odkazují sem.

## 1) Architektura projektu

miPress běží jako host Laravel aplikace + lokální Composer path balíčky:

- `packages/mipress/core`
- `packages/mipress/forms`
- `packages/mipress/social-feeds`

Host aplikace (`app/`) řeší panel provider, auth model `User`, root routes a root seedery.

## 2) Aktuální stack

- PHP 8.4
- Laravel 13
- Filament 5
- Livewire 4
- Pest 4
- Tailwind 4

## 3) Modely a databáze

### 3.1 Tabulky

Používej aktuální naming bez `mipress_` prefixu.

Příklady:

- `entries`
- `pages`
- `collections`
- `blueprints`
- `settings`
- `global_sets`
- `taxonomies`
- `terms`

### 3.2 Model patterns

- Vždy explicitní `fillable`, `casts`, typed relationship return types.
- `Entry` a `Page` používají traits: workflow, SEO, revisions, audit, locks, soft deletes.
- `Collection`, `Taxonomy`, `Term` mají `SoftDeletes`.

### 3.3 Settings

- Primární API je helper `settings()` + `SettingsManager`.
- `global_set()` je kompatibilní wrapper, ne primární směr dalšího vývoje.

## 4) Filament conventions

### 4.1 Registrace

Registrace resources/pages je v `MiPressPlugin` převážně explicitní.

### 4.2 Navigace

- Používej aktuální cluster strukturu (`ContentCluster`, `WebCluster`) a existující navigation groups.
- Breadcrumbs nevypínej globálně; vypínají se cíleně na konkrétních list stránkách.

### 4.3 Forms a content

- `Entry`: hlavní obsah přes `Mason::make('data.content')` + Blueprint custom fields.
- `Page`: hlavní obsah přes `Mason::make('content')`.
- Blueprint custom pole řeš přes `FieldTypeRegistry` + `BlueprintFieldResolver`.
- Nezaváděj ad-hoc type switch logiku mimo registry/resolver, pokud to není nezbytné.

### 4.4 Média

- Používej Curator komponenty pro media fields.

## 5) Permissions a role

Role:

- `super_admin`
- `admin`
- `editor`
- `contributor`

Permissions seeduje `PermissionSeeder` v `packages/mipress/core/src/Database/Seeders/PermissionSeeder.php`.

Dodržuj existující klíče (`entry.*`, `settings.manage`, `taxonomy.*`, `global_set.*`, ...), nevymýšlej paralelní naming bez důvodu.

## 6) Testing

- Test framework: Pest.
- Root testy jsou v `tests/Feature`, `tests/Unit`, `tests/Browser`.
- Při přidávání nové funkcionality drž test naming a styl podle existujících testů.
- Pokud přidáváš komplexní doménu (taxonomy, revisions, social feeds), přidej cílené testy.

## 7) Co neměnit bez explicitního důvodu

- Nezaváděj prefix `mipress_` do existujících tabulek.
- Neobcházej `FieldTypeRegistry` pro Blueprint pole.
- Neměň primární settings přístup zpět na legacy key-value pattern.
- Nevypínej breadcrumbs globálně v panel provideru.

## 8) Dokumentace a source-of-truth

- Analýza stavu: `_analysis.md`
- Projektové pravidla (tento soubor): `.github/instructions/mipress.instructions.md`
- Historické snapshoty: pouze referenční, ne source-of-truth.
