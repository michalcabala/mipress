# ROADMAP_REFAKTORING

Aktualizováno: 12. dubna 2026
Owner: průběžně (tým + agent)
Primární cíl: dovést miPress k první produkční verzi bez zbytečného architektonického přepisu

## Jak s roadmapou pracovat

- Tento soubor je živý backlog pro stabilizaci směrem k v1.
- Každý úkol má stav: `TODO`, `IN_PROGRESS`, `DONE`, `BLOCKED`.
- P0 = před prvním produkčním releasem.
- P1 = ideálně před releasem, případně těsně po něm.
- P2 = po stabilizaci v1.

## Hotovo nebo už potvrzeno

1. `DONE` Entry a Page workflow podporuje draft, review, reject, schedule a publish flow.
2. `DONE` Hierarchy guardy pro Entry a Page mají regresní testy.
3. `DONE` Taxonomy resource má základní feature test coverage.
4. `DONE` Revisions UI a základní revision testy jsou v root suite.
5. `DONE` Produkční smoke testy pro klíčové public/admin endpointy a public form submit existují.

## P0 - blokery před první produkční verzí

1. `TODO` Zavést reálnou CI pipeline v `.github/workflows`:
   minimálně Pint, `php artisan test --compact` a smoke gate.
2. `TODO` Dopsat instalační bootstrap nové instance:
   permissions, role, první super admin a baseline settings flow.
3. `TODO` Uzavřít scheduler orchestrace pro publikaci:
   potvrdit a doplnit automatické publikování `Page`, nejen `Entry`.
4. `TODO` Zdokumentovat povinné produkční cron/queue procesy:
   scheduler, queue worker, social feed refresh, sitemap strategie.
5. `TODO` Připravit produkční env baseline:
   `APP_DEBUG=false`, async queue, mail provider, secret management, cache režim.
6. `TODO` Rozhodnout scope social-feeds pro v1:
   buď modul produkčně dotestovat, nebo ho z launch scope vědomě vyřadit.

## P1 - stabilizace release kandidáta

1. `TODO` Dopsat integrační testy social-feeds workflow:
   redirect/callback, refresh jobs, error handling, cache fallback.
2. `TODO` Přidat regresní test pro scheduler-level publikaci pages.
3. `TODO` Srovnat `RELEASE_CHECKLIST.md` s reálným stavem repozitáře:
   zejména CI workflow a release gates.
4. `TODO` Ověřit staging běh v produkčnějším režimu:
   queue mimo `sync`, cron aktivní, assets build, mail delivery.
5. `TODO` Rozhodnout package test strategy:
   přidat testy do balíčků, nebo explicitně potvrdit root suite jako canonical.

## P2 - refaktoring a dlouhodobé zlepšení

1. `TODO` Vytáhnout společné workflow/action UI z Entry/Page formulářů do sdíleného concernu, pokud to dál dává smysl.
2. `TODO` Sjednotit naming slovník `name` vs `title` tam, kde to zjednoduší API a formuláře.
3. `TODO` Doplnit základní observability workflow:
   failed jobs, log review, jednoduché health checks.
4. `TODO` Průběžně čistit historické dokumentační stopy, aby nevznikaly nové paralelní zdroje pravdy.

## Poznámky k prioritám

- Největší riziko pro v1 už není CRUD vrstva, ale provozní spolehlivost a opakovatelnost release.
- Pokud bude termín krátký, doporučený kompromis je:
  dokončit CI + bootstrap + scheduler + staging ověření a social-feeds případně odložit mimo launch scope.
- Bez uzavření P0 bodů bude release možný jen ručně a s vyšším rizikem regresí.
