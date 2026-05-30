<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackupManager;
use App\Support\OperationalEventRecorder;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'ops:backup';

    protected $description = 'Create a JSON snapshot of the application database.';

    public function handle(DatabaseBackupManager $backups, OperationalEventRecorder $events): int
    {
        if (! config('ops.backups_enabled', true)) {
            $this->warn('Backups are disabled.');

            $events->record([
                'category' => 'backup',
                'severity' => 'warning',
                'title' => 'Backup job skipped.',
                'message' => 'Backups are disabled by configuration.',
                'source' => 'ops:backup',
                'context' => ['reason' => 'disabled'],
                'resolved_at' => null,
            ]);

            return self::SUCCESS;
        }

        try {
            $result = $backups->backup();

            $events->record([
                'category' => 'backup',
                'severity' => 'success',
                'title' => 'Database backup completed.',
                'message' => 'A new database snapshot was created successfully.',
                'source' => 'ops:backup',
                'context' => $result,
                'resolved_at' => now(),
            ]);

            $this->info(sprintf(
                'Backup stored at %s (%d tables, %d records).',
                $result['path'],
                $result['table_count'],
                $result['record_count']
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $events->record([
                'category' => 'backup',
                'severity' => 'critical',
                'title' => 'Database backup failed.',
                'message' => $exception->getMessage(),
                'source' => 'ops:backup',
                'context' => ['error' => $exception->getMessage()],
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
