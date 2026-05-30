<?php

return [
    'payments_enabled' => env('OPS_PAYMENTS_ENABLED', true),
    'webhooks_enabled' => env('OPS_WEBHOOKS_ENABLED', true),
    'backups_enabled' => env('OPS_BACKUPS_ENABLED', true),
    'alerts_enabled' => env('OPS_ALERTS_ENABLED', true),
    'reconciliation_enabled' => env('OPS_RECONCILIATION_ENABLED', true),
    'backup_disk' => env('OPS_BACKUP_DISK', 'local'),
    'backup_prefix' => env('OPS_BACKUP_PREFIX', 'backups'),
    'reconciliation_window_hours' => env('OPS_RECONCILIATION_WINDOW_HOURS', 48),
];
