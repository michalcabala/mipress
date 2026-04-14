# miPress — Copilot Instructions (Aktuální)

Tento soubor je high-level instrukce pro práci v repozitáři.
Detailní canonical pravidla jsou v:

- `.github/instructions/mipress.instructions.md`

## 1) Projektový kontext

miPress je Laravel aplikace s lokálními path balíčky:

- `mipress/core`
- `mipress/forms`
- `mipress/social-feeds`

Host app (`app/`) řeší panel bootstrap, auth a integraci balíčků.

## 2) Stack

- PHP 8.4
- Laravel 13
- Filament 5
- Livewire 4
- Tailwind CSS 4
- Pest PHP 4

Důležité integrační balíčky:

- Curator
- Mason
- Filament Resource Lock
- Filament Select Tree
- Spatie Permission
- Spatie Sluggable
- Laravel Socialite

## 3) Architektura pravidla

- Preferuj změny uvnitř příslušného balíčku místo ad-hoc logiky v host app.
- Core provider: `MiPress\Core\MiPressServiceProvider`.
- Filament resources/pages jsou v `packages/mipress/*/src/Filament/**`.
- Nezaváděj nové top-level složky bez důvodu.

## 4) Filament pravidla

- Pro Laravel a Filament úlohy vždy nejdřív použij Laravel Boost `search-docs`, pokud je v aktuální relaci dostupný.
- Pokud `search-docs` v aktuální relaci není vystavený, explicitně to uveď a pak použij ostatní dostupné Laravel Boost nástroje.
- Drž se existující navigační a cluster struktury.
- Pro admin UI preferuj nativní Filament components a patterns před custom Blade/Tailwind řešením.
- `Entry` obsah drž jako Mason `data.content` + Blueprint custom fields.
- `Page` obsah drž jako Mason `content`.
- Blueprint pole řeš přes `FieldTypeRegistry` + `BlueprintFieldResolver`.
- Breadcrumbs vypínej selektivně na konkrétních list pages, ne globálně.
- Pro media pole používej Curator.

## 5) Modely a databáze

- Tabulky jsou bez `mipress_` prefixu.
- V modelech používej explicitní `fillable`, `casts` a typed vztahy.
- U content modelů respektuj existující trait stack (workflow, seo, revisions, audit, locks, soft-deletes).

## 6) Nastavení a SEO

- Primární runtime settings API: helper `settings()` přes `SettingsManager`.
- `global_set()` je kompatibilní wrapper.
- SEO vrstvu řeší `HasSeo`, `SeoResolver`, `GlobalSeoSettingsManager`.

## 7) Oprávnění

- RBAC je přes Spatie Permission + enum `UserRole`.
- Permission klíče seeduje `PermissionSeeder`.
- Při změnách autorizace drž stávající naming (`entry.*`, `taxonomy.*`, `settings.manage`, ...).

## 8) Testy

- Používej Pest.
- Primární test suite je v root `tests/`.
- Při změnách kritických domén přidávej regresní testy (revisions, taxonomy, social-feeds, workflow).

## 9) Co nedělat

- Nepřepisuj model instrukce zpět na `mipress_` prefix tabulek.
- Neobcházej `FieldTypeRegistry` ad-hoc switchem bez důvodu.
- Nevypínej breadcrumbs globálně.
- Neobcházej settings vrstvu přímým legacy key/value patternem.

## 10) Dokumentační source-of-truth

- Stav projektu: `_analysis.md`
- Canonical projektové instrukce: `.github/instructions/mipress.instructions.md`
- Path-specific wrappers: `.github/instructions/*.instructions.md`
