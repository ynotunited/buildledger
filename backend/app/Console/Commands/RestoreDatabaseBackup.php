<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackupManager;
use App\Support\OperationalEventRecorder;
use Illuminate\Console\Command;
use Throwable;

class RestoreDatabaseBackup extends Command
{
    protected $signature = 'ops:restore-backup {path : Backup path relative to the backup disk} {--force : Restore without confirmation}';

    protected $description = 'Restore the application database from a JSON backup snapshot.';

    public function handle(DatabaseBackupManager $backups, OperationalEventRecorder $events): int
    {
        if (! $this->option('force') && ! $this->confirm('This will overwrite the current database tables. Continue?')) {
            $this->warn('Restore cancelled.');

            return self::SUCCESS;
        }

        try {
            $result = $backups->restore((string) $this->argument('path'));

            $events->record([
                'category' => 'backup',
                'severity' => 'critical',
                'title' => 'Database backup restored.',
                'message' => 'A database snapshot was restored successfully.',
                'source' => 'ops:restore-backup',
                'context' => $result,
                'resolved_at' => now(),
            ]);

            $this->info(sprintf(
                'Restored %s (%d tables, %d records).',
                $result['path'],
                $result['table_count'],
                $result['record_count']
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $events->record([
                'category' => 'backup',
                'severity' => 'critical',
                'title' => 'Database backup restore failed.',
                'message' => $exception->getMessage(),
                'source' => 'ops:restore-backup',
                'context' => ['error' => $exception->getMessage()],
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
