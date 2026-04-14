---
applyTo: "packages/mipress/**/src/Models/**"
---

# Models Wrapper Instructions

Primární pravidla jsou v:

- `.github/instructions/mipress.instructions.md`

Tento wrapper doplňuje model-specific body:

- Používej aktuální tabulky bez `mipress_` prefixu.
- U modelů drž explicitní `fillable`, `casts`, typed vztahy.
- U content modelů zachovej trait composition (workflow/seo/revisions/audit/locks/soft-deletes) podle stávající implementace.
- Při přidání pole vždy synchronizuj: migrace + model casts/fillable + relevantní Filament form/table + test.
- Nepřepisuj settings přístup na legacy key-value; preferovaný směr je `Setting` + `SettingsManager` + helper `settings()`.
