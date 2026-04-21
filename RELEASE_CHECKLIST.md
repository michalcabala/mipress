# miPress Release Checklist (v1)

## 0. Hardening blockers

- [ ] `theme-files` endpoint vrací pouze veřejné assety z theme (`assets/*`), ne Blade/view/manifest soubory.
- [ ] Bootstrap super-admin nepoužívá známý fallback login/heslo; produkční hodnoty jsou explicitně nastavené a bezpečné.
- [ ] Proběhl staging rehearsal na MySQL s aktivním cron schedulerem, `QUEUE_CONNECTION=database` a reálným SMTP.
- [ ] Je potvrzený rollback owner, komunikační kanál a odpovědnost za post-release monitoring.

## 1. Release gates (GitHub)

- [ ] Branch protection je aktivní pro `main`.
- [ ] Mergování vyžaduje pull request (zakázat direct push).
- [ ] Required status checks obsahují (viz `.github/workflows/ci.yml`):
  - [ ] `lint` (Pint code style)
  - [ ] `tests` (full test suite)
  - [ ] `smoke` (ProductionSmokeTest)
- [ ] Mergování je povoleno pouze při zelené CI.

## 2. Pre-release (lokál/staging)

- [ ] Všechny změny jsou v commitech a pushnuté na remote.
- [ ] CI je zelená pro cílovou branch (lint, tests, smoke).
- [ ] `composer validate --strict --no-check-publish` je čistý v root skeletonu i balíčcích.
- [ ] Root skeleton používá veřejná Composer jména `michalcabala/*`.
- [ ] Release skeleton není závislý na lokálních `path` repositories.
- [ ] `post-create-project-cmd` nespouští automatické migrace; explicitní bootstrap pro vydaný skeleton je zdokumentovaný (`composer run setup:create-project`).
- [ ] Tagy `1.0.0` existují v `michalcabala/mipress-core`, `michalcabala/mipress-forms`, `michalcabala/mipress-social-feeds` a skeletonu.
- [ ] Lokální smoke test adminu:
  - [ ] Přihlášení do admin panelu `/mpcp`.
  - [ ] Otevření klíčových sekcí (Položky, Stránky, Formuláře, SEO).
  - [ ] Vytvoření a uložení testovacího obsahu.
- [ ] Testy projektu:
  - [ ] `composer test:smoke`
  - [ ] `composer test:ci`
- [ ] Frontend build:
  - [ ] `npm run build`
- [ ] Lokální produkční cache rehearsal:
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
  - [ ] `php artisan filament:cache-components`
  - [ ] Homepage a `/mpcp/login` odpovídají korektně.
  - [ ] Po rehearsal vráceno na čistý stav přes `php artisan optimize:clear`.
- [ ] Migrace bez destruktivních kroků:
  - [ ] `php artisan migrate --pretend`
  - [ ] Kontrola SQL výstupu (bez drop/truncate/reset).
- [ ] Seed databáze (pokud nová instance):
  - [ ] `php artisan db:seed` (permissions, role, admin, global sets)
- [ ] Staging smoke v produkčnějším režimu:
   - [ ] Homepage a admin `/mpcp` fungují po `config:cache`, `route:cache`, `view:cache`.
   - [ ] Queue worker zpracuje notifikaci nebo refresh job.
   - [ ] Scheduler publikuje naplánovaný obsah.
   - [ ] Formulář doručí e-mail přes skutečný mailer.

## 3. Production deploy

- [ ] Aktivovat maintenance mode (pokud je potřeba):
  - [ ] `php artisan down --render="errors::503"`
- [ ] Aktualizovat kód na release commit/tag.
- [ ] Instalace backend závislostí:
  - [ ] `composer install --no-dev --optimize-autoloader --no-interaction`
- [ ] Instalace frontend závislostí + build:
  - [ ] `npm ci`
  - [ ] `npm run build`
- [ ] Spustit migrace:
  - [ ] `php artisan migrate --force --no-interaction`
- [ ] Vymazat/obnovit cache:
  - [ ] `php artisan optimize:clear`
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
  - [ ] `php artisan filament:cache-components`
- [ ] Restart queue workerů:
  - [ ] `php artisan queue:restart`
- [ ] Deaktivovat maintenance mode:
  - [ ] `php artisan up`

## 4. Post-release verifikace

- [ ] Otevřít produkční homepage a admin panel.
- [ ] Otestovat login a autorizace (Admin/Editor/Contributor).
- [ ] Ověřit vytvoření/uložení obsahu (Entry/Page).
- [ ] Ověřit formulář (odeslání + doručení mailu).
- [ ] Ověřit SEO sekci (robots/sitemap) v adminu.
- [ ] Zkontrolovat logy na kritické chyby.
- [ ] Ověřit, že queue zpracovává jobs bez failů.
- [ ] Ověřit, že scheduler běží (`php artisan schedule:list`).
- [ ] Ověřit, že naplánovaný obsah se publikuje.

## 5. Rollback plan

- [ ] Mít připravený předchozí stabilní release commit/tag.
- [ ] Při kritickém problému:
  - [ ] Přepnout kód na předchozí release.
  - [ ] Spustit `composer install --no-dev --optimize-autoloader --no-interaction`.
  - [ ] Spustit `php artisan optimize:clear`.
  - [ ] Spustit `php artisan queue:restart`.
- [ ] Databázi nerollbackovat destruktivně bez explicitního schválení.

## 6. Provozní dokumentace

Pro detailní popis produkčních procesů (scheduler, queue, env baseline, instalace) viz `DEPLOYMENT.md`.
