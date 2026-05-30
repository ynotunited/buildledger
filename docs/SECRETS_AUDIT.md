# Secrets Audit

## Findings

- No API keys, database passwords, or third-party service secrets are used in frontend code.
- Frontend environment variables are limited to public origin/base URL values:
  - `NEXT_PUBLIC_API_URL`
  - `NEXT_PUBLIC_BACKEND_URL`
- Backend secrets are read from server-side environment variables through Laravel config.

## Risks Found

- A live `backend/.env` file exists in the workspace and currently contains real credentials.
- `docker-compose.yml` previously hardcoded a database password.

## Fixes Applied

- Added a root `.gitignore` so local env files are not committed accidentally.
- Added `frontend/.env.example` so public frontend envs can be recreated safely without committing `.env.local`.
- Replaced the hardcoded Compose database password with `DB_PASSWORD`.
- Bound the local Postgres port to `127.0.0.1` instead of all network interfaces.

## What Still Needs Manual Action

- Rotate the Google OAuth client secret because it has already been exposed in this workspace history/chat.
- Confirm `backend/.env` is not committed in your remote repository history.
- If it was ever committed, remove it from git history and rotate any secrets that appeared there.
