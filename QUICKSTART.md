# miPress Quickstart

miPress je pro osobni projekty nejjednodussi pouzivat jako starter repo. Pro bezny novy web potrebujes hlavne dva prikazy:

```bash
composer run setup
composer run deploy
```

## 1. Zalozeni noveho webu

Preferovana cesta je zalozit novy repozitar z tohoto skeletonu jako template nebo clone.

1. Vytvor `.env` z `.env.example`.
2. Nastav minimalne `APP_URL`, `DB_*`, `MIPRESS_ADMIN_EMAIL` a `MIPRESS_ADMIN_PASSWORD`.
3. Spust:

```bash
composer run setup
```

`composer run setup` automaticky:

- doinstaluje Composer zavislosti, pokud jeste nejsou pritomne,
- vygeneruje `APP_KEY`,
- udela frontend build,
- spusti migrace a seed,
- vytvori `storage:link`,
- publikuje theme assety,
- procisti cache.

Po dokonceni se prihlas do adminu na `/mpcp`, pokud admin path vedome nezmenis v aplikaci.

## 2. Alternativa pres Composer create-project

Pokud skeleton publikujes jako Composer projekt, muzes pouzit i:

```bash
composer create-project michalcabala/mipress muj-web
cd muj-web
# uprav .env
composer run setup
```

`setup:create-project` zustava jen jako kompatibilni alias na stejny installer.

## 3. Deploy

Kanonicky deploy prikaz pro osobni projekty je:

```bash
composer run deploy
```

Deploy provede:

- `composer install --no-dev`,
- frontend build, pokud je k dispozici `npm`,
- bezpecnostni kontrolu `php artisan migrate --pretend`,
- maintenance mode,
- migrace,
- `storage:link`,
- publikaci theme assetu,
- `optimize:clear` + produkcni cache,
- `queue:restart`,
- navrat aplikace z maintenance mode.

Pokud na hostingu `npm` neni, deploy script build preskoci a necha stavajici build artefakty beze zmeny.

## 4. cPanel deploy

Soubor `cpanel.yml` vola stejny deploy script, takze manualni i automaticky deploy pouzivaji jednu sekvenci kroku.

Podrobnosti jsou v `DEPLOYMENT.md`.