# BuildLedger Operations Runbook

This app now includes a small operational toolkit for production support.

## Health And Alerts

- `php artisan ops:health-scan`
- Records a deployment readiness snapshot.
- Raises an admin alert if a check fails or warns.

## Backups

- `php artisan ops:backup`
- Creates a JSON snapshot of the current database in the configured backup disk.

To restore a snapshot:

- `php artisan ops:restore-backup backups/buildledger-db-YYYYMMDD_HHMMSS.json --force`

## Payment Reconciliation

- `php artisan ops:reconcile-payments`
- Replays the immutable ledger against invoices and subscriptions.
- Heals paid invoices from captured ledger entries.
- Flags stale checkouts and orphaned ledger entries in the operational feed.

## Launch Controls

These environment flags control incident response and production readiness:

- `OPS_PAYMENTS_ENABLED`
- `OPS_WEBHOOKS_ENABLED`
- `OPS_BACKUPS_ENABLED`
- `OPS_ALERTS_ENABLED`
- `OPS_RECONCILIATION_ENABLED`

## Recommended Schedule

- Health scan: hourly
- Payment reconciliation: hourly
- Database backup: daily at off-peak hours

## Recovery Workflow

1. Stop incoming traffic if the issue is severe.
2. Run `php artisan ops:health-scan` to capture the current deployment state.
3. Run `php artisan ops:backup` before any manual repair.
4. Use `php artisan ops:reconcile-payments` if payment state looks inconsistent.
5. Restore from a backup only if the live database is beyond repair.
