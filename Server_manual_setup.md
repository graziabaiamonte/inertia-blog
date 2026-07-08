<!-- processo iniziale che creava un clone di tutta la cartella,  non necessario in quanto per ottimizzare gli sapzi del droplet, ci mandiamo solo i file buildati. -->

# Setup manuale del server (DigitalOcean, senza Docker)

Documenta il deploy manuale dell'app su un droplet Ubuntu 24.04, seguito passo dopo passo durante la sessione di setup. Utile come riferimento per rifare il deploy da zero o per capire cosa toccare in caso di problemi.

**Droplet:** `test-grazia-ubuntu-s-1vcpu-512mb-10gb-fra1` — IP `164.92.182.114`, Ubuntu 24.04 LTS, 512MB RAM.

**Nota importante:** questo è un setup "manuale" (LAMP-style con Nginx + PHP-FPM), diverso da Sail/Docker usato in sviluppo locale. Sail **non va usato in produzione** su questo droplet — qui PHP, MySQL e Nginx sono installati nativamente.

---

## 1. Swap (RAM insufficiente)

Il droplet ha solo 512MB RAM — l'installazione di `mysql-server` è andata in OOM-kill durante `dpkg --configure`. Fix: creare uno swapfile prima di procedere con installazioni pesanti (MySQL, Composer, npm build).

```bash
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab   # persiste al reboot
```

Se `dpkg` resta bloccato da un lock di un processo precedente (es. dopo un kill per OOM):

```bash
sudo dpkg --configure -a   # se dice "frontend lock was locked by pid X"
sudo kill -9 <pid>
sudo dpkg --configure -a   # riprova
```

## 2. MySQL

```bash
sudo apt update
sudo apt install mysql-server
sudo systemctl status mysql   # deve essere "active (running)"
```

`root@localhost` su Ubuntu usa il plugin `auth_socket` — niente password, va con `sudo mysql` (non `mysql -u root -p`).

Creazione database e utente applicativo (stessi nomi usati in `.env.example`, ma **password diversa e reale** in produzione, non quella nel repo):

```sql
sudo mysql

CREATE DATABASE first_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'admin'@'localhost' IDENTIFIED WITH mysql_native_password BY '<password-reale>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES ON first_blog.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;
```

**Bug incontrato:** grant iniziale senza `REFERENCES` → `php artisan migrate` falliva su `create_permission_tables` con `SQLSTATE[42000]: ... 1142 REFERENCES command denied`, perché quella migration crea foreign key (`model_has_permissions` → `permissions`). `REFERENCES` è necessario per creare vincoli FK, non solo per leggere le tabelle referenziate.

**Bug incontrato:** dopo il fallimento, la tabella `permissions` era rimasta creata a metà (la migration si ferma a metà istruzioni SQL ma non viene segnata come eseguita). Riprovare `migrate` dava `Table 'permissions' already exists`. Fix: droppare a mano le tabelle orfane create dalla migration fallita, poi rilanciare `php artisan migrate --force` (riparte dalla migration mancante, non tocca quelle già segnate come eseguite).

## 3. PHP

Il server usava di default PHP 8.2 (repository Ubuntu), ma **Laravel 13 + le dipendenze del progetto richiedono PHP ^8.3**, e il `composer.lock` risolve `symfony/*` in v8.1.0 che richiede PHP **≥8.4.1**. Il PPA `ondrej/php` era già presente sul sistema (installato come dipendenza di `composer` via apt), quindi si installa PHP 8.4 direttamente:

```bash
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-mbstring \
  php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-common

sudo update-alternatives --set php /usr/bin/php8.4
php -v   # deve mostrare 8.4.x
```

## 4. Composer

```bash
sudo apt install -y composer
composer --version
```

## 5. Clone del repository

```bash
sudo mkdir -p /var/www/inertia_blog
sudo chown $USER:$USER /var/www/inertia_blog
git clone https://github.com/graziabaiamonte/inertia-blog.git /var/www/inertia_blog
cd /var/www/inertia_blog
git checkout deploy-digitalocean   # branch di cui vuoi il deploy
```

## 6. Dipendenze PHP

```bash
composer install --optimize-autoloader --no-dev
```

Su droplet con poca RAM, se va OOM:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-dev
```

## 7. `.env` di produzione

```bash
cp .env.example .env
nano .env
```

Valori cambiati rispetto a `.env.example` (che contiene i valori di **sviluppo locale/Sail**, non validi qui):

```
APP_ENV=production
APP_DEBUG=false                      # MAI true in produzione — mostra stack trace pubblici
APP_URL=http://164.92.182.114        # o https://dominio quando c'è SSL

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=first_blog
DB_USERNAME=admin
DB_PASSWORD=<password-reale-del-passo-2>
```

**Bug incontrato:** primo tentativo di `php artisan key:generate` falliva con `Failed to open stream: No such file or directory` perché il `.env` non era stato ancora creato (comando `cp` non eseguito in quella sessione di terminale).

```bash
php artisan key:generate
```

## 8. Storage & medialibrary

```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

`spatie/laravel-medialibrary` scrive in `storage/app/public`; senza il symlink le immagini dei post non sono raggiungibili da `public/storage/...`.

## 9. Migrazioni

```bash
php artisan migrate --force
```

(Vedi bug REFERENCES/tabelle orfane al punto 2.)

## 10. Build frontend

Node non era installato. Versione da apt (Ubuntu 24.04 → Node 18.x) è datata per Vite/React 19/Tailwind v4, installata invece via NodeSource:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v

npm ci
npm run build
```

Genera `public/build/` (manifest, JS, CSS) — necessario perché in produzione non c'è il dev server Vite.

**Bug incontrato — pagina bianca con errore "Unable to locate file in Vite manifest: resources/js/Pages/Blog/Index.tsx":**
`resources/views/app.blade.php` chiamava `@vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])`, cioè si aspettava che ogni pagina Inertia fosse un entry point Vite separato. Ma `vite.config.js` ha solo `resources/js/app.tsx` come `input`, e `app.tsx` bundla già tutte le pagine con `import.meta.glob('./Pages/**/*.tsx', { eager: true })`. In dev funzionava (il dev server Vite compila al volo qualsiasi path), in build statica no (il manifest non contiene le pagine come entry singoli). **Fix:** rimossa la seconda voce, lasciato solo `@vite(['resources/js/app.tsx'])` — commit `31480c2` su `deploy-digitalocean`.

## 11. Nginx

```bash
sudo nano /etc/nginx/sites-available/inertia_blog
```

Server block (adattato da un template standard Laravel+Nginx, `root` sul progetto, socket PHP-FPM 8.4):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name 164.92.182.114;
    root /var/www/inertia_blog/public;
    index index.php index.html index.htm;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob:; script-src 'self' 'unsafe-inline' http: https:; style-src 'self' 'unsafe-inline' http: https:;" always;

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\. {
        deny all;
    }

    location ~ /(\.env|\.git|composer\.(json|lock)|package\.json) {
        deny all;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/inertia_blog /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

**Bug incontrato:** `gzip_proxied ... must-revalidate auth;` → `nginx -t` falliva con `invalid value "must-revalidate"`. `must-revalidate` non è un valore valido per `gzip_proxied` (valori ammessi: `off expired no-cache no-store private no_last_modified no_etag auth any`). Rimosso.

**Bug incontrato — pagina bianca, console: "Executing inline script violates ... Content-Security-Policy ... default-src 'self' ...":**
Il CSP del template (`default-src 'self' http: https: data: blob;`, senza `script-src` esplicito) blocca qualsiasi `<script>` inline nella pagina. Ziggy (`@routes` in `app.blade.php`) inietta `<script>window.Ziggy = {...}</script>` inline per esporre le route Laravel a React — bloccato dal CSP, pagina bianca. **Fix:** aggiunto `script-src 'self' 'unsafe-inline' ...` e `style-src` analogo al CSP.

## 12. Seeder e Factory (popolare il database)

```bash
php artisan db:seed --force
```

**Bug incontrato #1 — permessi log:**

```
The stream or file "/var/www/inertia_blog/storage/logs/laravel.log" could not be opened in append mode: Permission denied
```

`storage/` era owned da `www-data` (impostato al punto 8), ma `artisan` viene lanciato come utente `grazia`, non in gruppo `www-data`. Fix: condividere ownership tra utente e gruppo web server:

```bash
sudo chown -R grazia:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Bug incontrato #2 — `Call to undefined function Database\Factories\fake()`:**
Le Factory usano l'helper `fake()`, fornito dal package `fakerphp/faker`, che nel `composer.json` di Laravel è tipicamente in `require-dev`. Il punto 6 installava le dipendenze con `--no-dev`, quindi `fakerphp/faker` non era presente in produzione → seeding falliva. Fix: reinstallare le dipendenze includendo quelle di sviluppo prima di seminare:

```bash
composer install --optimize-autoloader   # senza --no-dev
php artisan db:seed --force
```

Compromesso accettato per questo progetto demo: i package `require-dev` restano installati sul server anche dopo il seeding (per tornare "puliti" si potrebbe rilanciare `composer install --no-dev`, ma non è necessario per uno scopo di tirocinio/demo).

---

## Come funziona questo setup — domande frequenti

### È come se sul server ci fosse una copia del branch locale?

Sì, esattamente. `/var/www/inertia_blog` sul droplet è un **clone Git indipendente** dello stesso repository GitHub, con il suo checkout del branch `deploy-digitalocean`. Non c'è nessun collegamento live tra il tuo Mac e il droplet — sono due copie separate della stessa storia Git, sincronizzate solo tramite push/pull passando da GitHub (origin).

```
[Mac locale]  --git push-->  [GitHub: origin/deploy-digitalocean]  --git pull-->  [Droplet]
```

### Se modifico in locale e faccio push, devo sempre pull + build sul droplet?

Sì. Ogni volta che vuoi che le modifiche appaiano online, sul droplet:

```bash
cd /var/www/inertia_blog
git pull
composer install --optimize-autoloader --no-dev   # solo se sono cambiate dipendenze PHP (composer.json/lock)
npm ci                                              # solo se sono cambiate dipendenze JS (package.json/lock)
npm run build                                       # SEMPRE se hai toccato file in resources/js o resources/css
php artisan migrate --force                         # solo se ci sono nuove migration
php artisan config:cache && php artisan route:cache # opzionale, per performance
```

Perché serve sempre: il browser degli utenti scarica i file statici già compilati in `public/build/` (JS/CSS), generati da `npm run build`. React/TypeScript/Tailwind non vengono eseguiti "dal vivo" sul server come farebbe il dev server Vite in locale — il codice sorgente in `resources/js` non viene mai servito direttamente in produzione. Se pushi ma non rifai la build sul droplet, il sito continua a servire la build vecchia (i sorgenti `.tsx` cambiano, ma il bundle compilato no).

Lo stesso vale lato PHP: se cambi codice in `app/`, `routes/`, ecc., basta il `git pull` (PHP viene interpretato a runtime, non serve build) — ma se aggiungi un package Composer o una migration, serve rispettivamente `composer install` e `migrate`.

### Automatizzare questo processo

Questo flusso manuale (SSH + pull + build ogni volta) è il minimo indispensabile; è tipico automatizzarlo in un secondo momento con:

- un **webhook GitHub** che triggera uno script sul droplet ad ogni push, oppure
- **GitHub Actions** che si connette via SSH e fa pull+build automaticamente, oppure
- uno strumento di deploy come **Deployer** o **Envoyer** pensato apposta per Laravel.

Per ora, dato lo scopo demo/tirocinio, il flusso manuale documentato sopra è sufficiente.

---

## 13. Automazione del deploy con GitHub Actions (da clone manuale a CI/CD)

Il flusso manuale ai punti 1–12 resta il **setup iniziale** del droplet (fatto una sola volta: swap, MySQL, PHP, Nginx, primo `git clone`). Da un certo punto in poi, però, il deploy di ogni nuova modifica non è più stato fatto a mano (SSH + `git pull` + build), ma automatizzato con **GitHub Actions**.

### 13.1 Situazione di partenza (cosa c'era prima)

`/var/www/inertia_blog` era un **clone Git completo** del repository: oltre ai file necessari a runtime (`app/`, `config/`, `public/`, `vendor/`, ecc.) conteneva anche tutto ciò che serve solo in sviluppo — `.git/`, `node_modules/`, `tests/`, `resources/js` (sorgenti TS/TSX non compilati), file di progetto (`PLAN.md`, `CLAUDE.md`, `ImprovementPlan.md`, `ToDo.md`, `README.md`), tooling (`.vscode/`, `.ide-stubs/`, `phpunit.xml`, `pint.json`, `tailwind.config.js`, `vite.config.js`, `package.json`, `compose.yaml`), ecc. Ogni aggiornamento richiedeva login SSH manuale e la sequenza `git pull` + `composer install` + `npm run build` + `migrate` descritta sopra.

### 13.2 Obiettivo del cambiamento

Automatizzare il deploy **senza** portare sul droplet l'intero repository, ma solo i file realmente necessari all'esecuzione dell'app in produzione — e senza buildare su una macchina con soli 512MB RAM (già andata in OOM su `composer`/`npm`, vedi punto 1).

### 13.3 Architettura scelta

- **Build sempre in CI** (runner GitHub Actions, RAM abbondante), mai sul droplet.
- **Trasferimento selettivo** via `rsync` (non più `git pull` sul server): solo le cartelle/file di runtime.
- Scrittura **in-place** dentro `/var/www/inertia_blog` (la stessa cartella di sempre), non uno schema a release/symlink separato — per restare coerenti con Nginx già puntato lì (`root /var/www/inertia_blog/public`).
- `.env` e `storage/` **mai toccati** dal deploy automatico: restano quelli già presenti e gestiti a mano sul droplet.

### 13.4 File del workflow

- `.github/workflows/_deploy-prod.yml` — reusable workflow (`workflow_call`), job unico `build-and-deploy`:
  1. `actions/checkout@v4`
  2. `composer install --no-dev --optimize-autoloader` (PHP 8.4, come sul droplet)
  3. `npm install && npm run build` → genera `public/build/` (manifest, JS, CSS)
  4. `rsync -az --relative --delete` verso il droplet, con allowlist esplicita di path:
     ```
     app bootstrap config database public routes resources/views lang vendor artisan composer.json composer.lock
     ```
     escludendo `--exclude='.env'` e `--exclude='storage/'`.
  5. Via SSH sul droplet: fix permessi `storage`/`bootstrap/cache`, `php artisan migrate --force`, `optimize:clear`, poi `config:cache` + `route:cache` + `view:cache`, `systemctl reload php8.4-fpm`.
- `.github/workflows/_deploy-trigger.yml` — trigger: push sul branch `deploy-digitalocean` chiama `_deploy-prod.yml`.

### 13.5 Configurazione richiesta su GitHub (una tantum)

Repository → Settings → Secrets and variables → Actions:

- **Variables**: `TARGET_HOST_PROD` (`164.92.182.114`), `TARGET_PORT_PROD` (`22`), `TARGET_USER_PROD` (`grazia`), `TARGET_PATH_PROD` (`/var/www/inertia_blog`).
- **Secrets**: `DEPLOY_KEY_PROD` (chiave privata SSH dedicata, es. `~/.ssh/id_ed25519`; la pubblica corrispondente va in `~/.ssh/authorized_keys` sul droplet per l'utente `grazia`).

Sul droplet, l'utente usato dal deploy deve poter eseguire senza password i comandi che richiedono `sudo` (chown storage, reload php-fpm):
```bash
sudo visudo -f /etc/sudoers.d/github-deploy
```
con dentro una riga tipo:
```
grazia ALL=(ALL) NOPASSWD: /bin/chown, /bin/chmod, /usr/bin/systemctl reload php8.4-fpm
```

### 13.6 Bug incontrato — rsync appiattisce `resources/views` in una cartella `views/` top-level

Prima versione del comando rsync passava più path come argomenti separati senza `--relative`:
```bash
rsync -az --delete app bootstrap ... resources/views lang ... user@host:/var/www/inertia_blog/
```
Comportamento di `rsync` con path multipli: ogni sorgente viene copiata sul target usando solo il proprio **nome base**, non l'intero percorso relativo. Risultato: invece di popolare `/var/www/inertia_blog/resources/views/`, ha creato una cartella sbagliata `/var/www/inertia_blog/views/` (nome base di `resources/views`), lasciando le Blade view nel posto sbagliato.

**Fix:** aggiunta l'opzione `--relative` (alias `-R`) al comando rsync, che preserva il percorso relativo completo di ogni sorgente rispetto alla working directory del checkout:
```bash
rsync -az --relative --delete ...
```
Con questa opzione `resources/views` finisce correttamente sotto `/var/www/inertia_blog/resources/views/`.

Fix applicato, poi rimossa manualmente sul droplet la cartella `views/` sbagliata creata dal run precedente:
```bash
rm -rf /var/www/inertia_blog/views
```

### 13.7 Bug incontrato — `Class "translator" does not exist` / `Class "...BrowserLocaleServiceProvider" not found` dopo il deploy

Sintomo: dopo il primo deploy automatico riuscito, il sito rispondeva con **500 Internal Server Error**. Nel log (`storage/logs/laravel.log`) compariva `ReflectionException: Class "translator" does not exist`, e in un secondo momento (dopo `php artisan optimize:clear` manuale) `Class "CodeZero\BrowserLocale\Laravel\BrowserLocaleServiceProvider" not found`.

**Causa:** il comando rsync escludeva esplicitamente `bootstrap/cache/*.php` dal trasferimento, nell'idea (sbagliata) che fossero cache "runtime" come `storage/` da preservare tra un deploy e l'altro. In realtà `bootstrap/cache/packages.php` e `bootstrap/cache/services.php` sono **artefatti generati da Composer** (package auto-discovery, eseguito durante `composer install`) che elencano i service provider da caricare in base ai pacchetti effettivamente installati. Escludendoli, il droplet continuava a usare la versione generata al tempo del *primo* clone manuale (quando erano installati anche pacchetti `require-dev` non più presenti nel `vendor/` buildato con `--no-dev` dalla CI) — disallineata col nuovo `vendor/`, causando provider mancanti o non trovati.

**Fix:**
1. Rimosso `--exclude='bootstrap/cache/*.php'` dal comando rsync — ora tutta `bootstrap/cache/` viene sincronizzata ad ogni deploy, sempre coerente col `vendor/` appena installato dalla CI.
2. Aggiunto `php artisan optimize:clear` nello script SSH, **prima** di rigenerare `config:cache`/`route:cache`/`view:cache`, per eliminare ogni cache stantia residua ad ogni deploy.
3. Fix una tantum applicato a mano sul droplet per sbloccare il sito subito (senza aspettare un nuovo deploy):
   ```bash
   rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
   php artisan package:discover --ansi
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   sudo systemctl reload php8.4-fpm
   ```

### 13.8 Pulizia una tantum del droplet dai file di sviluppo residui

Dato che `/var/www/inertia_blog` era nato come clone Git completo (punto 13.1), è stato ripulito manualmente da tutto ciò che il nuovo workflow rsync non gestisce e che non serve a runtime:

```bash
cd /var/www/inertia_blog
rm -rf .git .github .claude .ide-stubs .vscode node_modules tests output views \
       .editorconfig .gitattributes .gitignore .npmrc .prettierignore .prettierrc \
       AGENTS.md CLAUDE.md ImprovementPlan.md PLAN.md README.md ToDo.md \
       compose.yaml package.json package-lock.json phpunit.xml pint.json \
       postcss.config.js tailwind.config.js tsconfig.json vite.config.js
rm -rf resources/js resources/css   # sorgenti frontend grezzi, già compilati in public/build
```

Da quel momento in poi il workflow **non ricrea** questi file: vivono solo nel checkout temporaneo del runner GitHub Actions durante la build, mai sul droplet.

### 13.9 Bug incontrato — immagini dei post 404 dopo il deploy (symlink `public/storage` cancellato)

Sintomo: sito online, ma tutte le immagini caricate tramite `spatie/laravel-medialibrary` (featured image dei post, immagini inline) davano **404** in console (`Failed to load resource: 404`), pur essendo presenti fisicamente in `storage/app/public/...` sul droplet.

**Causa:** `public/storage` non è un file versionato in git — è un **symlink** creato a runtime da `php artisan storage:link` (fatto manualmente al punto 8 del setup iniziale), che punta a `storage/app/public`. Il comando rsync sincronizza `public/` con `--delete` rispetto al checkout CI, che ovviamente **non contiene** questo symlink (non fa parte del repository). Ad ogni deploy, rsync lo cancellava per "farlo combaciare" con la sorgente CI — rompendo tutti i link alle immagini pur senza cancellare i file reali in `storage/`.

**Fix in `_deploy-prod.yml`:**
1. Aggiunto `--exclude='public/storage'` al comando rsync, per non farlo mai toccare/cancellare dal `--delete`.
2. Aggiunto `php artisan storage:link` nello script SSH post-deploy (prima di `optimize:clear`), idempotente: se il symlink esiste già non fa nulla di distruttivo, se manca (es. primo deploy su droplet pulito) lo ricrea.

Fix una tantum per sbloccare subito il sito (senza aspettare un nuovo deploy):
```bash
cd /var/www/inertia_blog
php artisan storage:link
```

### 13.10 Stato finale di `/var/www/inertia_blog`

Dopo l'automazione, la cartella contiene **solo**:

```
app/  bootstrap/  composer.json  composer.lock  config/  database/
lang/  public/  routes/  resources/views/  storage/  vendor/  artisan  .env
```

- `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `lang/`, `resources/views/`, `vendor/`, `artisan`, `composer.json`/`composer.lock` → sincronizzati automaticamente ad ogni push su `deploy-digitalocean` (rsync li sovrascrive e ripulisce con `--delete`).
- `public/` → sincronizzato allo stesso modo, include gli asset compilati in `public/build/` (JS/CSS pronti, generati da `npm run build` in CI — **non** i sorgenti `resources/js`/`resources/css`, che non vengono mai portati sul droplet).
- `.env` e `storage/` → **mai toccati** dal deploy automatico, esclusi esplicitamente da rsync: restano quelli configurati/gestiti manualmente sul droplet (credenziali reali, upload utenti, log).
- `node_modules/` → **non esiste mai** sul droplet: `npm install`/`npm run build` girano solo sul runner GitHub Actions (macchina temporanea, distrutta a fine job), e `node_modules/` non fa parte dell'allowlist rsync — serve solo a produrre `public/build/`, non a runtime.

### 13.11 Come rifare un deploy da adesso in poi

Basta un push sul branch `deploy-digitalocean` dal Mac:
```bash
git push origin deploy-digitalocean
```
GitHub Actions builda (`composer install --no-dev`, `npm install && npm run build`), sincronizza via rsync e ricachea automaticamente — non serve più login SSH manuale salvo troubleshooting.

> **Nota:** è stato brevemente valutato (e poi scartato) uno schema alternativo in cui `composer install`/`npm install` giravano direttamente sulla VPS invece che in CI, per restare più vicini al flusso manuale dei punti 6/10. Scartato perché avrebbe portato `node_modules/` (mai necessario a runtime) a comparire stabilmente sul droplet — si è preferito mantenere la build in CI, come descritto sopra.

### 13.12 Cambio meccanismo di trasferimento — da `rsync` a `tar` + `scp` + estrazione, `composer install` spostato sulla VPS

Ispirandosi al workflow reale usato in azienda (`.github/workflows/deploy-dev.yml`, che usa uno schema a "release" con cartelle `releases/<sha>/` + symlink — quella parte **non** è stata replicata, si resta sullo schema in-place già in uso), il meccanismo di trasferimento dei file è cambiato da `rsync` a `tar`+`scp`+estrazione via SSH. Contestualmente, `composer install` si sposta dalla CI alla VPS (`npm install && npm run build` **restano in CI**, il tar include già `public/build/` pronto).

**Nuovo flusso in `_deploy-prod.yml`:**
1. CI: `npm install && npm run build`.
2. CI: crea un archivio `tar.gz` (nome = SHA del commit) con la stessa allowlist di file di produzione usata finora, **esclusa `vendor/`**: `app bootstrap config database public routes resources/views lang artisan composer.json composer.lock`.
3. CI: upload dell'archivio in `/tmp` sul droplet via `appleboy/scp-action`.
4. Via SSH sul droplet:
   - rimozione esplicita delle vecchie directory runtime (`app bootstrap config database routes resources/views lang public/build`) prima di estrarre — **necessario** perché, a differenza di `rsync --delete`, l'estrazione di un tar non cancella da sola i file non più presenti nel nuovo archivio;
   - estrazione dell'archivio direttamente in `/var/www/inertia_blog`;
   - cancellazione dell'archivio da `/tmp` subito dopo l'estrazione;
   - `composer install --optimize-autoloader --no-dev` (con `COMPOSER_MEMORY_LIMIT=-1`, stesso motivo del punto 6 — rischio OOM su 512MB RAM) — **nuovo**, prima girava in CI;
   - permessi, `migrate --force`, `storage:link`, `optimize:clear`, cache, reload PHP-FPM — invariati.

**Perché `vendor/` non è più sincronizzato ma nemmeno generato in CI:** ora viene creato/aggiornato direttamente sulla VPS da `composer install`, eseguito dopo l'estrazione del tar. Il tar non lo contiene affatto (né come sorgente né come vincolo di pulizia — non essendo nell'allowlist, `rm -rf` prima dell'estrazione non lo tocca).

**Nota sulla pulizia mancante di `tar` rispetto a `rsync --delete`:** con `rsync --delete` i file rimossi dal codice sparivano automaticamente dal droplet ad ogni deploy. Con `tar`+estrazione questo non succede in automatico — per questo lo script SSH cancella esplicitamente le directory dell'allowlist prima di ri-estrarre, per evitare che vecchi file PHP/Blade rimossi dal repository restino orfani sul server.
