---
applyTo: "tests/**"
---

# Tests Wrapper Instructions

Primární pravidla jsou v:

- `.github/instructions/mipress.instructions.md`

Tento wrapper doplňuje test-specific body:

- Test framework: Pest.
- Dodržuj existující styl root test suite (`tests/Feature`, `tests/Unit`, `tests/Browser`).
- Při změnách ve workflow/revisions/taxonomy/social-feeds přidávej cílené regresní testy.
- Preferuj čitelné názvy testů podle domény (resource/feature behavior), ne generické názvy.
