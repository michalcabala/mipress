# miPress Release Checklist (v1)

## 0. Release gates (GitHub)

- [ ] Branch protection je aktivní pro `main`.
- [ ] Mergování vyžaduje pull request (zakázat direct push).
- [ ] Required status checks obsahují (viz `.github/workflows/ci.yml`):
  - [ ] `lint` (Pint code style)
  - [ ] `tests` (full test suite)
  - [ ] `smoke` (ProductionSmokeTest)
- [ ] Mergování je povoleno pouze při zelené CI.

## 1. Pre-release (lokál/staging)

- [ ] Všechny změny jsou v commitech a pushnuté na remote.
- [ ] CI je zelená pro cílovou branch (lint, tests, smoke).
- [ ] Lokální smoke test adminu:
  - [ ] Přihlášení do admin panelu `/mpcp`.
  - [ ] Otevření klíčových sekcí (Položky, Stránky, Formuláře, SEO).
  - [ ] Vytvoření a uložení testovacího obsahu.
- [ ] Testy projektu:
  - [ ] `composer test:smoke`
  - [ ] `composer test:ci`
- [ ] Frontend build:
  - [ ] `npm run build`
- [ ] Migrace bez destruktivních kroků:
  - [ ] `php artisan migrate --pretend`
  - [ ] Kontrola SQL výstupu (bez drop/truncate/reset).
- [ ] Seed databáze (pokud nová instance):
  - [ ] `php artisan db:seed` (permissions, role, admin, global sets)

## 2. Production deploy

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

## 3. Post-release verifikace

- [ ] Otevřít produkční homepage a admin panel.
- [ ] Otestovat login a autorizace (Admin/Editor/Contributor).
- [ ] Ověřit vytvoření/uložení obsahu (Entry/Page).
- [ ] Ověřit formulář (odeslání + doručení mailu).
- [ ] Ověřit SEO sekci (robots/sitemap) v adminu.
- [ ] Zkontrolovat logy na kritické chyby.
- [ ] Ověřit, že queue zpracovává jobs bez failů.
- [ ] Ověřit, že scheduler běží (`php artisan schedule:list`).
- [ ] Ověřit, že naplánovaný obsah se publikuje.

## 4. Rollback plan

- [ ] Mít připravený předchozí stabilní release commit/tag.
- [ ] Při kritickém problému:
  - [ ] Přepnout kód na předchozí release.
  - [ ] Spustit `composer install --no-dev --optimize-autoloader --no-interaction`.
  - [ ] Spustit `php artisan optimize:clear`.
  - [ ] Spustit `php artisan queue:restart`.
- [ ] Databázi nerollbackovat destruktivně bez explicitního schválení.

## 5. Provozní dokumentace

Pro detailní popis produkčních procesů (scheduler, queue, env baseline, instalace) viz `DEPLOYMENT.md`.
