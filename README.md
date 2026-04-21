# miPress

miPress je modularni CMS postavene na Laravelu 13, Filamentu 5 a lokalnich Composer baliccich.
Root repozitar funguje jako skeleton aplikace; CMS kernel a volitelne moduly jsou rozdelene do balicku v `packages/mipress/*`.

Aktualni priorita projektu je priprava prvniho ostreho release tak, aby slo zalozit novy web/projekt pres Composer s co nejtensim skeletonem a jasnou hranici mezi host app a CMS balicky.

## Stack

- PHP 8.4
- Laravel 13
- Filament 5
- Livewire 4
- Tailwind CSS 4
- Pest 4

## Balicky

- `mipress/core`: CMS kernel, content modely, admin resources/pages, public rendering, themes, SEO, settings, workflow, media
- `mipress/forms`: formularovy modul, submit flow a administrace odpovedi
- `mipress/social-feeds`: social account/feed integrace a scheduled refresh

## Lokalni instalace

Zakladni instalace z checkoutu:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Jednokrokovy installer pro cisty checkout:

```bash
composer run setup
```

Installer provede Composer install, pripravi `.env`, vygeneruje `APP_KEY`, postavi frontend, spusti migrace a seedy, vytvori storage link, procisti cache a pusti smoke test.

Pokud se ma pri prvni instalaci vytvorit bootstrap admin, nastav v `.env`:

```env
MIPRESS_ADMIN_EMAIL=admin@example.test
MIPRESS_ADMIN_PASSWORD=<secure-random>
```

## Provozni poznamky

- Admin panel je dostupny na `/mpcp`.
- Verejny web pouziva routes registrovane z balicku, ne z root `routes/web.php`.
- Theme assety jsou verejne dostupne pouze z `assets/*`.
- Produkcni deploy a release checklist jsou popsane v `DEPLOYMENT.md` a `RELEASE_CHECKLIST.md`.

## Release smer

Repo je momentalne monorepo se `path` repositories. To je pohodlne pro vyvoj, ale pro prvni Composer-ready release jeste zbyva doresit:

- finalni verejne Composer package naming skeletonu,
- versioned constraints misto internich `@dev` odkazu,
- explicitni contract toho, co zustava v root skeletonu a co vlastni `mipress/core`.

Aktualni backlog a refaktoring priority jsou v `ROADMAP_REFAKTORING.md`.
