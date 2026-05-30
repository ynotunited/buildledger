# Production Env Checklist

This project now has separate production templates so you can keep testing locally without changing your active local `.env` files.

Files to use:

- Backend production template: `backend/production.env.example`
- Frontend production template: `frontend/production.env.example`

## Keep local testing as-is

Your current local backend file is still fine for development:

- `APP_ENV=local`
- `APP_DEBUG=true`
- `APP_URL=http://localhost:8000`
- `FRONTEND_URL=http://localhost:3000`
- `DB_CONNECTION=mysql`
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`

Do not replace that file while you are still testing locally.

## What changes in production

### Backend

Change these from local to production:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://api.yourdomain.com`
- `FRONTEND_URL=https://app.yourdomain.com`
- `DB_CONNECTION=pgsql`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_SECURE_COOKIE=true`
- `SECURITY_API_GATEWAY_ENFORCED=true`

### Secrets you must provide

- `APP_KEY`
- `DB_PASSWORD`
- `API_GATEWAY_SHARED_SECRET`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`

### Secrets only if you use the feature

- Google login:
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
- Paystack:
  - `PAYSTACK_SECRET_KEY`
  - `PAYSTACK_PUBLIC_KEY`
- Flutterwave:
  - `FLUTTERWAVE_SECRET_KEY`
  - `FLUTTERWAVE_PUBLIC_KEY`
  - `FLUTTERWAVE_WEBHOOK_HASH`
- Cloud storage:
  - `AWS_ACCESS_KEY_ID`
  - `AWS_SECRET_ACCESS_KEY`
  - `AWS_BUCKET`

## Important note about Google

Your current local Google client secret has already been exposed in chat/workspace history, so rotate it before production use.

## Recommended rollout flow

1. Keep using current `.env` for localhost work.
2. Copy `backend/production.env.example` into your deployment platform secret manager or a real production env file outside git.
3. Copy `frontend/production.env.example` into your frontend deployment settings.
4. Fill the real secrets.
5. Run `php artisan security:deployment-check`.
6. Verify `/readyz` is healthy before opening traffic.
