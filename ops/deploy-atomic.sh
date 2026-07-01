#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="/var/www/buildledger"
RELEASES_DIR="$APP_ROOT/releases"
SHARED_DIR="$APP_ROOT/shared"
CURRENT_LINK="$APP_ROOT/current"
RELEASE_ID="$(date +%Y%m%d%H%M%S)"
RELEASE_DIR="$RELEASES_DIR/$RELEASE_ID"
REPO_URL="${REPO_URL:-https://github.com/ynotunited/buildledger.git}"
BRANCH="${BRANCH:-main}"

echo "==> Creating release $RELEASE_ID"
mkdir -p "$RELEASES_DIR" "$SHARED_DIR"

echo "==> Fetching source"
git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$RELEASE_DIR"

echo "==> Linking shared environment files if present"
if [ -f "$SHARED_DIR/backend.env" ]; then
  cp "$SHARED_DIR/backend.env" "$RELEASE_DIR/backend/.env"
elif [ -f "$APP_ROOT/backend/.env" ]; then
  cp "$APP_ROOT/backend/.env" "$RELEASE_DIR/backend/.env"
fi

if [ -f "$SHARED_DIR/frontend.env.local" ]; then
  cp "$SHARED_DIR/frontend.env.local" "$RELEASE_DIR/frontend/.env.local"
elif [ -f "$APP_ROOT/frontend/.env.local" ]; then
  cp "$APP_ROOT/frontend/.env.local" "$RELEASE_DIR/frontend/.env.local"
fi

echo "==> Installing backend dependencies"
(
  cd "$RELEASE_DIR/backend"
  composer install --no-dev --optimize-autoloader --no-interaction
)

echo "==> Installing frontend dependencies"
(
  cd "$RELEASE_DIR/frontend"
  npm ci --legacy-peer-deps
)

echo "==> Building frontend"
(
  cd "$RELEASE_DIR/frontend"
  npm run build
)

echo "==> Flipping current symlink"
ln -sfn "$RELEASE_DIR" "$CURRENT_LINK"

echo "==> Restarting frontend process"
(
  cd "$CURRENT_LINK/frontend"
  pm2 start npm --name buildledger-frontend -- start || true
  pm2 restart buildledger-frontend --update-env
  pm2 save
)

echo "==> Release $RELEASE_ID deployed successfully"
