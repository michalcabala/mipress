# miPress

miPress je modularni CMS postavene na Laravelu 13, Filamentu 5 a Composer baliccich.
Root repozitar ma pro osobni projekty fungovat hlavne jako starter pro novy web; oddelene CMS balicky jsou implementacni detail, ne primarni onboarding krok.

Pro nejrychlejsi create/deploy flow viz `QUICKSTART.md`.

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

## Doporuceny flow pro osobni projekty

Preferovana cesta je zalozit novy web jako template nebo clone tohoto skeletonu, pripravit `.env` a spustit:

```bash
composer run setup
```

`composer run setup` je kanonicky installer pro novy web. Sam rozpozna, jestli uz Composer zavislosti existuji, a pak dokonci zbytek bootstrapu.

## Instalace noveho projektu

Minimalni setup pro cisty checkout:

1. vytvor `.env` z `.env.example`,
2. nastav databazi, `APP_URL` a volitelne bootstrap admin ucet,
3. spust `composer run setup`.

Installer provede Composer install jen pokud je potreba, pripravi `.env`, vygeneruje `APP_KEY`, postavi frontend, spusti migrace a seedy, vytvori `storage:link`, publikuje theme assety a procisti cache.

Pokud budes pouzivat `composer create-project`, finish flow je stejny: po uprave `.env` spust zase `composer run setup`. `setup:create-project` zustava jen jako kompatibilni alias.

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

## Deploy

Kanonicky deploy prikaz pro osobni projekty je:

```bash
composer run deploy
```

Tento skript udela `composer install --no-dev`, buildne frontend pokud je pritomne `npm`, zkontroluje migrace pres `php artisan migrate --pretend`, prepne aplikaci do maintenance mode, spusti migrace, obnovi symlinky a theme assety, procisti cache, znovu vytvori produkcni cache a restartuje queue worker.

`cpanel.yml` vola stejny deploy script, aby manualni i automaticky deploy mely stejnou sekvenci kroku.

## Balicky a release model

Release model je nyni ukotveny takto:

- skeleton balicek je `michalcabala/mipress`,
- CMS kernel je `michalcabala/mipress-core`,
- forms modul je `michalcabala/mipress-forms`,
- social feeds modul je `michalcabala/mipress-social-feeds`.

Aktualni stav release modelu:

- root skeleton pouziva verejna Composer jmena `michalcabala/*`,
- release skeleton uz neni zavisly na lokalnich `path` repositories, protoze balicky resi pres verejne GitHub `git` zdroje bez zavislosti na GitHub API driveru,
- create-project bootstrap uz neprovadi automaticke migrace a seedy bez vedomeho kroku installera.

Pro osobni projekty to neni blocker. Public `composer create-project`/Packagist je volitelny release smer navic, ne podminka bezneho pouziti.

Aktualni backlog a refaktoring priority jsou v `ROADMAP_REFAKTORING.md`.
