# BuildLedger Observability Setup

This guide covers the three observability tools used by BuildLedger:

- Sentry for error tracking
- PostHog for product analytics
- BetterStack for uptime and incident alerts

## What Each Tool Does

- Sentry captures frontend and backend exceptions, stack traces, and request context.
- PostHog tracks page views and product events so you can see how people move through the app.
- BetterStack monitors public endpoints so you know when the app or API is down.

## Sentry

Create one Sentry project for BuildLedger and copy the DSNs for both the frontend and backend.

Use these env values:

- `backend/.env`
  - `SENTRY_LARAVEL_DSN`
  - `SENTRY_ENVIRONMENT`
  - `SENTRY_TRACES_SAMPLE_RATE`
- `frontend/.env.local`
  - `NEXT_PUBLIC_SENTRY_DSN`
  - `NEXT_PUBLIC_SENTRY_ENVIRONMENT`
  - `NEXT_PUBLIC_SENTRY_TRACES_SAMPLE_RATE`

Recommended first pass:

- `SENTRY_TRACES_SAMPLE_RATE=0.1`
- `NEXT_PUBLIC_SENTRY_TRACES_SAMPLE_RATE=0.1`

That is enough to capture crash clues without sending too much traffic while the app is still growing.

## PostHog

Create a PostHog project and set:

- `NEXT_PUBLIC_POSTHOG_KEY`
- `NEXT_PUBLIC_POSTHOG_HOST`

The app defaults to `https://us.i.posthog.com`, but you should use the region shown in your PostHog project settings if it is different.

PostHog is wired to:

- capture page views
- identify signed-in users
- carry the user role into analytics

## BetterStack

Monitor these URLs:

- `https://buildledger.madeitcodes.online`
- `https://api.buildledger.madeitcodes.online/readyz`

The `/readyz` endpoint is the authoritative backend health signal for BuildLedger.

## Localhost Notes

For local development, keep the Sentry and PostHog env values blank until you create test projects. The app will still run normally without them.

## After You Add Keys

Whenever you update frontend or backend env values on the VPS:

1. Clear caches if needed.
2. Rebuild the frontend.
3. Restart PM2.
4. Confirm `/readyz` still returns `200`.
