# ROADMAP_REFAKTORING

Aktualizováno: 11. dubna 2026
Owner: průběžně (Copilot + tým)

## Cíl

Zvýšit konzistenci workflow, snížit duplicitní/legacy kód a doplnit ochrany proti datovým chybám bez rozbití současných flow v adminu.

## Pravidla práce s roadmapou

- Tento soubor je živý backlog.
- Každý úkol má stav: `TODO`, `IN_PROGRESS`, `DONE`, `BLOCKED`.
- Po každé významné změně aktualizovat datum a stav.

## Fáze A — Konsolidace workflow plánování

1. `DONE` Zavést jednotné zacházení s plánovanou publikací (`scheduled_at` + kompatibilita s `published_at`).
2. `DONE` Upravit command `entries:publish-scheduled`, aby publikoval i legacy naplánované záznamy konzistentně.
3. `TODO` Rozhodnout dlouhodobě canonical zdroj času plánování (jen `scheduled_at` vs dual režim).

## Fáze B — Hierarchie a validace rodičů

1. `DONE` Přidat guard proti cyklům v parent stromu při editaci Entry/Page.
2. `TODO` Přidat stejné guardy do všech relevantních create/edit flow včetně Terms (pokud je to potřeba).
3. `TODO` Dodat regresní testy pro cykly v hierarchii.

## Fáze C — Cleanup a redukce dead kódu

1. `DONE` Odstranit nevyužité legacy builder metody ve formulářích Entry/Page.
2. `TODO` Vytáhnout společné status/action UI z EntryForm/PageForm do sdíleného concernu.
3. `TODO` Sjednotit naming slovník (`name` vs `title`) napříč modely a formuláři.

## Fáze D — Testovací dluh

1. `TODO` Dopsat Feature testy pro Taxonomy/Term resources.
2. `TODO` Dopsat testy pro social-feeds workflows.
3. `TODO` Dopsat explicitní testy scheduling edge-cases a revisions edge-cases.

## Poznámky k implementaci

- Zachovat backwards compatibility tam, kde je produkční data závislost.
- Nepoužívat destruktivní migrační kroky bez explicitního schválení.
