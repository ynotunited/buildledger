# Atomic Deployment

The live-design breakage happens when the server exposes HTML from one build while the browser requests CSS and JS chunks from another.

The fix is to stop updating the live directory in place. Build into a fresh release directory, verify it, then flip a `current` symlink only after the build succeeds.

## Release layout

Use this structure on the VPS:

```text
/var/www/buildledger/
  releases/
    20260701153045/
    20260701160211/
  shared/
  current -> /var/www/buildledger/releases/20260701160211
```

`current` is the only path that PM2 and Nginx should read from.

On the first migration, move your existing live environment files into `shared/`:

```bash
mkdir -p /var/www/buildledger/shared
cp /var/www/buildledger/backend/.env /var/www/buildledger/shared/backend.env
cp /var/www/buildledger/frontend/.env.local /var/www/buildledger/shared/frontend.env.local
```

## Why this works

- Next.js fingerprints CSS and JS per build.
- Each release is self-contained, so one deploy cannot partially overwrite the previous one.
- The old release stays live until the new one has already built successfully.
- Browser cache stops being a source of mixed-build breakage because the server never serves a half-swapped tree.

## VPS deploy flow

1. Create a new release directory.
2. Clone or copy the repo into that directory.
3. Restore shared `.env` files and persistent storage.
4. Install dependencies and run the production build inside the release.
5. Verify the build output exists.
6. Atomically point `current` to the new release.
7. Restart PM2 from the `current` path.
8. Keep at least one previous release for rollback.

## Recommended frontend start command

PM2 should launch the app from the `current` symlink, not from a mutable checkout:

```bash
cd /var/www/buildledger/current/frontend
pm2 start npm --name buildledger-frontend -- start
pm2 save
```

After each deploy:

```bash
cd /var/www/buildledger/current/frontend
pm2 restart buildledger-frontend --update-env
pm2 save
```

## Atomic deploy script

Use the script in `ops/deploy-atomic.sh` as the deploy entrypoint on the VPS. It builds a new release first and only swaps the live symlink when everything succeeds.
