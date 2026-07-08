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
