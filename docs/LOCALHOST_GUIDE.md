# BuildLedger Localhost Guide

This guide explains how to run BuildLedger locally on your machine, what each service does, the main URLs to use, and the most common troubleshooting steps.

## Project Layout

BuildLedger is a monorepo with two separate apps:

- `backend/` - Laravel API, authentication, billing, notifications, admin tooling, and background jobs.
- `frontend/` - Next.js app for the public landing page, auth screens, dashboard, and public payment pages.

The two apps talk to each other over HTTP:

- Frontend: `http://localhost:3000`
- Backend: `http://localhost:8000`

## What You Need Installed

- PHP 8.3 or compatible Laravel runtime
- Composer
- Node.js 20+
- npm
- MySQL
- Redis

Optional but recommended:

- Mailpit or MailHog for local email capture
- Git

## First-Time Setup

### 1) Clone the repository

```bash
git clone https://github.com/ynotunited/buildledger.git
cd buildledger
```

### 2) Backend setup

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan storage:link
```

Then make sure your backend `.env` points to your local services:

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=buildledger
DB_USERNAME=root
DB_PASSWORD=
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

If you want local email capture, set:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@buildledger.local
MAIL_FROM_NAME="BuildLedger"
```

Then run migrations:

```bash
php artisan migrate
```

### 3) Frontend setup

```bash
cd ../frontend
cp production.env.example .env.local
npm install
```

Set the frontend env values for localhost:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_BACKEND_URL=http://localhost:8000
```

## Run the App

Open two terminals.

### Backend terminal

```bash
cd backend
php artisan serve
```

### Frontend terminal

```bash
cd frontend
npm run dev
```

## Local URLs

- Landing page: `http://localhost:3000`
- Login: `http://localhost:3000/login`
- Register: `http://localhost:3000/register`
- Dashboard: `http://localhost:3000/dashboard`
- Admin: `http://localhost:3000/admin`
- Backend API: `http://localhost:8000/api`

## Local Test Accounts

If you are using the seeded local users created during development, these credentials should work:

- Admin: `admin@madeitcodes.online`
- Password: `M4deItC0des!Admin#7Qv9`
- Owner: `test.owner@buildledger.local`
- Password: `BuildLedger123!`
- Client: `test.client@buildledger.local`
- Password: `BuildLedgerClient123!`

If you recreate your local database, these users may need to be re-seeded.

## Common Workflows

### Log in with Google locally

Your Google OAuth app should include the local callback:

```text
http://localhost:8000/api/auth/google/callback
```

and the local frontend origin:

```text
http://localhost:3000
```

### Capture local emails

If Mailpit is running, open:

```text
http://localhost:8025
```

This is useful for:

- email verification
- password reset
- invoice payment link emails
- waitlist invitations

### Run backend checks

```bash
cd backend
php artisan test
```

### Run frontend checks

```bash
cd frontend
npm run lint
npm run build
```

## Troubleshooting

### 1) CSRF token mismatch

Make sure:

- `NEXT_PUBLIC_API_URL` is set to `http://localhost:8000/api`
- Laravel Sanctum stateful domains include `localhost:3000` and `localhost:8000`
- your browser cookies for `localhost` are not stale

### 2) Login redirects loop

If the login page keeps refreshing:

- hard refresh the browser
- clear `localhost` cookies
- confirm the frontend is pointing to the correct API URL

### 3) Google login fails

Check the Google Cloud Console settings:

- Authorized JavaScript origin: `http://localhost:3000`
- Authorized redirect URI: `http://localhost:8000/api/auth/google/callback`

Then clear Laravel caches:

```bash
cd backend
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

### 4) Emails do not appear

If you use Mailpit:

- verify the backend mail config points to `127.0.0.1:1025`
- open `http://localhost:8025`

If you use a real SMTP provider:

- confirm the credentials in `backend/.env`
- check `storage/logs/laravel.log`

### 5) Frontend build fails on dependency resolution

Use the same approach we use in production when npm complains about peer dependencies:

```bash
npm install --legacy-peer-deps
```

## Notes

- The app is invite-only in its current launch mode, so new accounts may require approval depending on your local database state.
- Public pages are the marketing site, legal pages, and public payment/signing pages.
- Private pages like dashboard, billing, admin, and clients require authentication.

