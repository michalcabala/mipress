# miPress

miPress je modularni CMS postavene na Laravelu 13, Filamentu 5 a verejnych Composer baliccich.
Root repozitar funguje jako skeleton aplikace; CMS kernel a moduly jsou publikovane jako samostatne Composer balicky a skeleton je sklada dohromady.

Aktualni priorita projektu je priprava prvniho ostreho release tak, aby slo zalozit novy web/projekt pres Composer s co nejtensim skeletonem a jasnou hranici mezi host app a CMS balicky.

## Stack

- PHP 8.4
- Laravel 13
- Filament 5
- Livewire 4
- Tailwind CSS 4
- Pest 4

## Balicky

- `michalcabala/mipress-core`: CMS kernel, content modely, admin resources/pages, public rendering, themes, SEO, settings, workflow, media
- `michalcabala/mipress-forms`: formularovy modul, submit flow a administrace odpovedi
- `michalcabala/mipress-social-feeds`: social account/feed integrace a scheduled refresh

## Instalace noveho projektu

Cilovy public install flow je:

```bash
composer create-project michalcabala/mipress muj-web
cd muj-web
composer run setup:create-project
```

Skeleton si zavislosti taha z verejnych GitHub repozitaru `michalcabala/mipress-core`,
`michalcabala/mipress-forms` a `michalcabala/mipress-social-feeds`.

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

Pro `composer create-project` flow je zamer jiny: create-project hook pouze pripravi `.env` a vypise dalsi instrukci, ale nespousti databazove zmeny automaticky. `APP_KEY` se vygeneruje az v explicitnim installeru po doplneni `.env`. Nasledovat ma:

```bash
composer run setup:create-project
```

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

Release model je nyni ukotveny takto:

- skeleton balicek je `michalcabala/mipress`,
- CMS kernel je `michalcabala/mipress-core`,
- forms modul je `michalcabala/mipress-forms`,
- social feeds modul je `michalcabala/mipress-social-feeds`.

Aktualni stav release modelu:

- root skeleton pouziva verejna Composer jmena `michalcabala/*`,
- release skeleton uz neni zavisly na lokalnich `path` repositories, protoze balicky resi pres verejne GitHub `git` zdroje bez zavislosti na GitHub API driveru,
- create-project bootstrap uz neprovadi automaticke migrace a seedy bez vedomeho kroku installera.

Zbyvajici externi krok mimo tento repozitar je registrace root skeletonu na Packagist, aby sel volat primo pres `composer create-project michalcabala/mipress` bez dalsich repository argumentu.

Aktualni backlog a refaktoring priority jsou v `ROADMAP_REFAKTORING.md`.
