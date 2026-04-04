# miPress Release Checklist (v1)

## 0. Release gates (GitHub)

- [ ] Branch protection je aktivni pro `main`.
- [ ] Mergovani vyzaduje pull request (zakazat direct push).
- [ ] Required status checks obsahuji:
  - [ ] `smoke`
  - [ ] `test`
- [ ] Mergovani je povoleno pouze pri zelene CI.

## 1. Pre-release (lokal/staging)

- [ ] Vsechny zmeny jsou v commitech a pushnute na remote.
- [ ] CI je zelena pro cilovou branch (build, Pint, testy).
- [ ] Lokalni smoke test adminu:
  - [ ] Prihlaseni do admin panelu `/mpcp`.
  - [ ] Otevreni klicovych sekci (Polozky, Stranky, Formulare, SEO).
  - [ ] Vytvoreni a ulozeni testovaciho obsahu.
- [ ] Testy projektu:
  - [ ] `composer test:smoke`
  - [ ] `composer test:ci`
- [ ] Frontend build:
  - [ ] `npm run build`
- [ ] Migrace bez destruktivnich kroku:
  - [ ] `php artisan migrate --pretend`
  - [ ] Kontrola SQL vystupu (bez drop/truncate/reset).

## 2. Production deploy

- [ ] Aktivovat maintenance mode (pokud je potreba):
  - [ ] `php artisan down --render="errors::503"`
- [ ] Aktualizovat kod na release commit/tag.
- [ ] Instalace backend zavislosti:
  - [ ] `composer install --no-dev --optimize-autoloader --no-interaction`
- [ ] Instalace frontend zavislosti + build:
  - [ ] `npm ci`
  - [ ] `npm run build`
- [ ] Spustit migrace:
  - [ ] `php artisan migrate --force --no-interaction`
- [ ] Vymazat/obnovit cache:
  - [ ] `php artisan optimize:clear`
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
- [ ] Restart queue workeru:
  - [ ] `php artisan queue:restart`
- [ ] Deaktivovat maintenance mode:
  - [ ] `php artisan up`

## 3. Post-release verifikace

- [ ] Otevrit produkcni homepage a admin panel.
- [ ] Otestovat login a autorizace (Admin/Editor/Contributor).
- [ ] Overit vytvoreni/ulozeni obsahu (Entry/Page).
- [ ] Overit formular (odeslani + doruceni mailu).
- [ ] Overit SEO sekci (robots/sitemap) v adminu.
- [ ] Zkontrolovat logy na kriticke chyby.
- [ ] Overit, ze queue zpracovava jobs bez failu.

## 4. Rollback plan

- [ ] Mit pripraveny predchozi stabilni release commit/tag.
- [ ] Pri kritickem problemu:
  - [ ] Prepnout kod na predchozi release.
  - [ ] Spustit `composer install --no-dev --optimize-autoloader --no-interaction`.
  - [ ] Spustit `php artisan optimize:clear`.
  - [ ] Spustit `php artisan queue:restart`.
- [ ] Databazi nerollbackovat destruktivne bez explicitniho schvaleni.
