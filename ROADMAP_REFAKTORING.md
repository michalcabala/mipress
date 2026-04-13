# ROADMAP_REFAKTORING

Aktualizováno: 14. dubna 2026
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
6. `DONE` Entry/Page list publication akce přepsány na workflow modal s `ToggleButtons` + `DateTimePicker` (13. 4.).
7. `DONE` Grouped actions konzistentně na všech resource listech (core, forms, social-feeds) (13. 4.).
8. `DONE` User resource: inline editace hesel odstraněna, nahrazena admin password reset + invitation flow (13. 4.).
9. `DONE` AuditLog model a tabulka `audit_logs` odstraněny (13. 4.).
10. `DONE` Resource Lock tabulky odstraněny (11. 4.).
11. `DONE` CuratorMedia: vlastní list/grid view, filtry, curations, grouped actions, ownership policy (12. 4.).
12. `DONE` Test infra: doplněna chybějící curator table migrace, opravena avatar FK reference, SQLite-kompatibilní hasForeignKey (13. 4.).

## P0 - blokery před první produkční verzí

1. `DONE` CI pipeline v `.github/workflows/ci.yml`: Pint lint, full test suite, smoke gate (13. 4.).
2. `DONE` Instalační bootstrap: `DatabaseSeeder` volá `PermissionSeeder` + `GlobalSetSeeder` + vytvoří default super admina (13. 4.).
3. `DONE` Scheduler pro Pages: `PublishScheduledPages` command + registrace v scheduleru (13. 4.).
4. `DONE` Zdokumentovat povinné produkční cron/queue procesy:
   scheduler, queue worker, social feed refresh, sitemap strategie.
   Viz `DEPLOYMENT.md` (13. 4.).
5. `DONE` Připravit produkční env baseline:
   `APP_DEBUG=false`, async queue, mail provider, secret management, cache režim.
   Viz `DEPLOYMENT.md` + `config/mipress.php` (13. 4.).
6. `TODO` Rozhodnout scope social-feeds pro v1:
   buď modul produkčně dotestovat, nebo ho z launch scope vědomě vyřadit.

## P1 - stabilizace release kandidáta

1. `TODO` Dopsat integrační testy social-feeds workflow:
   redirect/callback, refresh jobs, error handling, cache fallback.
2. `DONE` Přidat regresní test pro scheduler-level publikaci pages (3 testy v PageResourceTest, 13. 4.).
3. `DONE` Srovnat `RELEASE_CHECKLIST.md` s reálným stavem repozitáře:
   zejména CI workflow a release gates (13. 4.).
4. `TODO` Ověřit staging běh v produkčnějším režimu:
   queue mimo `sync`, cron aktivní, assets build, mail delivery.
5. `TODO` Rozhodnout package test strategy:
   přidat testy do balíčků, nebo explicitně potvrdit root suite jako canonical.
6. `DONE` Doplnit `declare(strict_types=1)` do 30 souborů (social-feeds: 26, host app: 4, forms: 0 — již měly) (14. 4.).
7. `DONE` Duplikátní `HasContextualCrudNotifications` v social-feeds již neexistuje (ověřeno 13. 4.).
8. `DONE` Return types doplněny na `SocialAuthController::redirect()`, `callback()` a `handleFacebookPages()` (13. 4.).

## P2 - refaktoring a dlouhodobé zlepšení

1. `DONE` Stale `$auditExclude` odstraněn z `Entry.php` a `Page.php` (13. 4.).
2. `TODO` Rozhodnout stav lokalizačních scopů (`scopeForLocale`, `scopeOriginals`) v Entry, Page, Term:
   buď nechat jako @future, nebo smazat pokud multi-lang nebude v dohlednu.
3. `DONE` `json_encode()` v `SocialPost::upsertFromApi()` je korektní — `upsert()` obchází Eloquent casty, json_encode je nutný (ověřeno 13. 4.).
4. `TODO` Přidat logování do tichých `catch (\Throwable) {}` bloků v social-feeds.
5. `TODO` Vytáhnout společné workflow/action UI z Entry/Page formulářů do sdíleného concernu, pokud to dál dává smysl.
6. `TODO` Sjednotit naming slovník `name` vs `title` tam, kde to zjednoduší API a formuláře.
7. `TODO` Doplnit základní observability workflow:
   failed jobs, log review, jednoduché health checks.
8. `TODO` Průběžně čistit historické dokumentační stopy, aby nevznikaly nové paralelní zdroje pravdy.
9. `TODO` Odstranit `app/Models/CuratorMedia.php` alias pokud se potvrdí, že není externě referencován.
10. `TODO` Zvážit doplnění avatar factory states a `declare(strict_types)` do `UserFactory.php`.

## Poznámky k prioritám

- Největší riziko pro v1 už není CRUD vrstva, ale provozní spolehlivost a opakovatelnost release.
- Code quality záležitosti (strict_types, dead code, trait duplikáty) jsou P1/P2, neblokují launch.
- Pokud bude termín krátký, doporučený kompromis je:
  dokončit CI + bootstrap + scheduler + staging ověření a social-feeds případně odložit mimo launch scope.
- Bez uzavření P0 bodů bude release možný jen ručně a s vyšším rizikem regresí.
