# miPress — Produkční provoz

## Požadované procesy

### 1. Scheduler (cron)

Scheduler musí běžet každou minutu. Na produkci/staging přidat do crontab:

```
* * * * * cd /path/to/mipress && php artisan schedule:run >> /dev/null 2>&1
```

Registrované úlohy:

| Příkaz / Job | Frekvence | Popis |
|---|---|---|
| `entries:publish-scheduled` | `everyMinute` | Publikuje naplánované Entries |
| `pages:publish-scheduled` | `everyMinute` | Publikuje naplánované Pages |
| `RefreshAllFeedsJob` | `hourly` (konfig.) | Obnovuje social feeds |

Social feeds frekvenci lze změnit v `config/social-feeds.php` (`refresh.schedule`).

### 2. Queue worker

Aplikace používá queue pro notifikace, social feeds refresh a další joby.

```bash
# Produkční spuštění (doporučeno supervisor/systemd):
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# Lokální vývoj (simple listener):
php artisan queue:listen --tries=1
```

Po každém deploy je nutný restart workeru:
```bash
php artisan queue:restart
```

Queue driver v `.env`:
- **Lokálně:** `QUEUE_CONNECTION=sync` nebo `database`
- **Produkce:** `QUEUE_CONNECTION=database` (nebo Redis, pokud je k dispozici)

### 3. Sitemap generování

Manuálně nebo jako scheduled task:
```bash
php artisan mipress:generate-sitemap
```

Pro automatické generování přidat do scheduleru v `bootstrap/app.php`:
```php
$schedule->command(GenerateSitemap::class)->daily();
```

### 4. Theme assety

Po přidání/změně theme vytvořit symlinky:
```bash
php artisan mipress:publish-assets
```

## Produkční env baseline

Minimální `.env` pro produkci:

```env
APP_NAME=miPress
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.cz

# Databáze
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mipress
DB_USERNAME=<secure>
DB_PASSWORD=<secure>

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (SMTP provider)
MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<smtp-user>
MAIL_PASSWORD=<smtp-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.cz
MAIL_FROM_NAME="${APP_NAME}"

# Vyžadováno pro automatické vytvoření bootstrap admina
MIPRESS_ADMIN_EMAIL=admin@your-domain.cz
MIPRESS_ADMIN_PASSWORD=<secure-random>
```

### Povinné kontroly před spuštěním

- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` odpovídá skutečné doméně
- [ ] Mail provider nastaven (ne `log`)
- [ ] Queue driver nastaven na `database` nebo `redis` (ne `sync`)
- [ ] Cron job pro scheduler je aktivní
- [ ] Queue worker běží (supervisor/systemd)
- [ ] `php artisan storage:link` provedeno
- [ ] `php artisan mipress:publish-assets` provedeno
- [ ] `MIPRESS_ADMIN_EMAIL` a `MIPRESS_ADMIN_PASSWORD` jsou vyplněné, pokud se má vytvořit bootstrap admin
- [ ] `php artisan db:seed` provedeno (permissions + roles + admin)

## Instalace nové instance

```bash
# 1. Závislosti
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Prostředí
cp .env.example .env
# Upravit .env dle produkčního baseline výše
php artisan key:generate

# 3. Databáze + seed
php artisan migrate --force
php artisan db:seed

# 4. Assety a symlinky
php artisan storage:link
php artisan mipress:publish-assets

# 5. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# 6. Spustit scheduler + queue worker
```

## Deploy flow (cPanel)

Deploy je automatizovaný přes `cpanel.yml` a spouští se push do `staging` branch.
Viz `cpanel.yml` v projektu pro přesnou sekvenci kroků.

Manuální staging deploy:
```bash
# V package repo: push tagu, pak v skeleton:
composer update mipress/core
git add composer.lock && git commit -m "chore: update mipress/core" && git push

# Merge do staging:
git checkout staging && git merge main --no-edit && git push origin staging
git checkout main
```
