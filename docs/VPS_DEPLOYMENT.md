# BuildLedger VPS Deployment

This guide is for deploying BuildLedger on an Ubuntu 22.04 VPS with:

- `buildledger.madeitcodes.online` for the Next.js frontend
- `api.buildledger.madeitcodes.online` for the Laravel API

The app is a monorepo:

- `backend/` = Laravel/PHP API
- `frontend/` = Next.js app

## 1. DNS

Create these DNS records at Hostinger:

- `A` record: `buildledger` -> your VPS IP
- `A` record: `api.buildledger` -> your VPS IP

Wait for propagation before continuing.

## 2. SSH into the VPS

```bash
ssh root@153.92.209.54
```

## 3. Install server packages

Run these line by line. If one command fails, stop and fix it before continuing.

```bash
apt update && apt upgrade -y
apt install -y nginx git curl unzip software-properties-common ca-certificates
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-pgsql php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd php8.3-sqlite3 php8.3-redis php8.3-soap
apt install -y redis-server postgresql certbot python3-certbot-nginx
```

If your VPS uses a different PHP version, adjust the `php8.3-fpm` socket in the Nginx config later.

Install a current Node.js LTS release before building the frontend. Next.js 16 needs a newer Node runtime than the default Ubuntu package provides:

```bash
apt remove -y nodejs npm libnode-dev || true
apt --fix-broken install -y
dpkg --configure -a
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
node -v
npm -v
```

If the install still complains about `common.gyp`, run `apt remove -y libnode-dev` again and repeat the NodeSource install step.

## 4. Install Composer

Run these line by line.

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

## 5. Clone the repo

Run these line by line.

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/ynotunited/buildledger.git
cd buildledger
```

If the repo stays private, the VPS needs a GitHub deploy key or a GitHub token with read access.

## 6. Backend setup

Run these line by line.

```bash
cd /var/www/buildledger/backend
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
```

Update `backend/.env` with production values:

- `APP_URL=https://api.buildledger.madeitcodes.online`
- `FRONTEND_URL=https://buildledger.madeitcodes.online`
- `DB_CONNECTION=pgsql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=5432`
- `DB_DATABASE=buildledger`
- `DB_USERNAME=buildledger`
- `DB_PASSWORD=...`
- `REDIS_HOST=127.0.0.1`
- `REDIS_PASSWORD=` if your Redis server does not use a password
- `SESSION_DOMAIN=.madeitcodes.online`
- `SANCTUM_STATEFUL_DOMAINS=buildledger.madeitcodes.online,api.buildledger.madeitcodes.online`
- `GOOGLE_REDIRECT_URI=https://api.buildledger.madeitcodes.online/api/auth/google/callback`

Also set your mail, payment, and gateway secrets.

If `php artisan cache:clear` still fails with a Redis name-resolution error, make sure the Redis service is running:

```bash
systemctl enable --now redis-server
systemctl status redis-server
```

Important: any `.env` value with spaces must be wrapped in quotes. For example:

```env
SECURITY_AI_PROMPT_USER_DELIMITER_START="BEGIN USER CONTENT"
SECURITY_AI_PROMPT_USER_DELIMITER_END="END USER CONTENT"
```

## 7. Frontend setup

Run these line by line.

```bash
cd /var/www/buildledger/frontend
cp production.env.example .env.local
npm ci --legacy-peer-deps
npm run build
```

If `npm ci` still complains about peer dependencies on the VPS, use the `--legacy-peer-deps` flag as shown above. This project currently includes React 19 with a dependency that still declares React 16-18 peers, so strict npm resolution can fail even though the app builds correctly.

Update `frontend/.env.local`:

```env
NEXT_PUBLIC_API_URL=https://api.buildledger.madeitcodes.online/api
NEXT_PUBLIC_BACKEND_URL=https://api.buildledger.madeitcodes.online
```

## 8. Run the frontend with PM2

You can paste this block together because each command depends on the previous one.

```bash
npm install -g pm2
cd /var/www/buildledger/frontend
pm2 start npm --name buildledger-frontend -- start
pm2 save
pm2 startup
```

## 9. Nginx config

Use `nano` to create the file, then paste the full Nginx block together. Do **not** paste the opening or closing triple backticks.

Run these line by line:

```bash
nano /etc/nginx/sites-available/buildledger
```

Create the file at:

`/etc/nginx/sites-available/buildledger`

```nginx
server {
    listen 80;
    server_name buildledger.madeitcodes.online;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}

server {
    listen 80;
    server_name api.buildledger.madeitcodes.online;

    root /var/www/buildledger/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable it:

Run these line by line.

```bash
ln -s /etc/nginx/sites-available/buildledger /etc/nginx/sites-enabled/buildledger
nginx -t
systemctl reload nginx
```

## 10. SSL

Run this as a single command.

```bash
certbot --nginx -d buildledger.madeitcodes.online -d api.buildledger.madeitcodes.online
```

## 11. Database

Create the PostgreSQL database and user, then run migrations:

Run the shell commands line by line. The SQL statements inside `psql` can be pasted together.

```bash
sudo -u postgres psql
```

```sql
CREATE USER buildledger WITH PASSWORD 'A?z0M#qk7IYeArKSN+l?';
CREATE DATABASE buildledger OWNER buildledger;
\q
```

Then:

Run this line by line.

```bash
cd /var/www/buildledger/backend
php artisan migrate --force
```

## 12. Final checks

Run these line by line.

```bash
curl -I https://buildledger.madeitcodes.online
curl -I https://api.buildledger.madeitcodes.online/readyz
```

If those return healthy responses, the app is live.

If `/up` returns 500 but `/readyz` returns 200, treat `/readyz` as the authoritative readiness check for BuildLedger. The app is still up; `/up` is the framework health route and may be affected by middleware or server configuration.

If the server checks are healthy but your browser shows `ERR_CONNECTION_RESET`, test from the Windows machine itself:

```powershell
ipconfig /flushdns
nslookup buildledger.madeitcodes.online
```

Make sure the DNS lookup returns the VPS IP, then try another browser or Incognito mode. If a VPN, proxy, antivirus web shield, or corporate firewall is enabled, disable it briefly and test again.

If `/readyz` returns `500`, run these backend checks:

```bash
cd /var/www/buildledger/backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
php artisan tinker --execute="dump(config('database.default'), config('cache.default'), config('session.driver'), config('security.row_level_security_required'), config('ops.payments_enabled'));"
```

Then retry:

```bash
curl -I https://api.buildledger.madeitcodes.online/readyz
```

## 13. Future updates

Do not deploy by overwriting the live checkout in place.

Use an atomic release instead:

```bash
sudo bash /var/www/buildledger/ops/deploy-atomic.sh
```

That script builds a new release first, then flips `/var/www/buildledger/current` only after the build passes.

If you need to run migrations after the release swap, run them from the new `current` path:

```bash
cd /var/www/buildledger/current/backend
php artisan migrate --force
```

## Notes

- This app is not a static website.
- The frontend and backend are separate services.
- The `api.buildledger.madeitcodes.online` subdomain is the Laravel API.
- The `buildledger.madeitcodes.online` subdomain is the Next.js frontend.
