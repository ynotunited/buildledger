# Secret Rotation Policy

This policy covers any secret used to run or support BuildLedger, including:

- `APP_KEY`
- database credentials
- Google OAuth client secrets
- payment gateway keys
- SMTP credentials
- PostHog, Sentry, BetterStack, or similar service tokens
- webhook signing secrets

## Policy

- Treat every production secret as sensitive and non-public.
- Never commit secrets to Git or paste them into chat, issue trackers, or screenshots.
- Keep secrets in the VPS environment, hosting provider secret store, or encrypted environment files only.
- Review production secrets at least quarterly.
- Rotate a secret immediately if it is exposed, suspected to be exposed, shared with the wrong person, or attached to a compromised environment file.
- Use the least-privilege scope available for each vendor key.
- Keep a simple secret inventory with:
  - secret name
  - system owner
  - where it is used
  - last rotation date
  - next review date
  - rotation steps
- After rotation, clear config caches and restart the affected service so the new value is loaded.

## Rotation Rules

- For third-party API keys, create a new key, update the app, verify the service, then revoke the old key.
- For SMTP credentials, switch the password first, then test mail delivery, then remove the old credential.
- For payment and webhook secrets, rotate during a low-traffic window and verify checkout, payment verification, and webhook delivery immediately after.
- For `APP_KEY`, use Laravel's key rotation flow and keep previous keys in `APP_PREVIOUS_KEYS` long enough to avoid breaking encrypted values during the transition.

## Encrypted Environment Files

If we need to keep an environment file in source control, use Laravel's encrypted env file workflow instead of plain text:

- encrypt the env file
- store the decrypt key separately
- restrict access to the decrypt key
- rotate the env encryption key if the encrypted file or decrypt key is exposed

## Incident Checklist

Use this checklist when you think a secret may have been exposed:

1. Confirm which secret is affected and where it was exposed.
2. Revoke or disable the exposed secret immediately if the vendor supports it.
3. Generate a replacement secret.
4. Update the VPS or deployment secret store with the new value.
5. Clear Laravel config/cache and restart the affected services.
6. Smoke test the affected flow:
   - login
   - email
   - payments
   - webhooks
   - monitoring
7. Revoke the old secret after the replacement is confirmed working.
8. Record the incident, rotation date, and any follow-up actions in the secrets inventory.

## Follow-Up

- Add the secret to the inventory if it was missing.
- Review whether any related credentials should also be rotated.
- If the exposure happened in source control, treat the leaked secret as compromised and rotate it even if it was later removed.
