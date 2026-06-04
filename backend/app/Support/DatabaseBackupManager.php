<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackupManager
{
    public function backup(): array
    {
        $disk = (string) config('ops.backup_disk', 'local');
        $prefix = trim((string) config('ops.backup_prefix', 'backups'), '/');
        $retentionDays = max(1, (int) config('ops.backup_retention_days', 30));
        $timestamp = now()->format('Ymd_His');
        $fileName = ($prefix !== '' ? "{$prefix}/" : '') . "buildledger-db-{$timestamp}.json.enc";

        $tables = [];
        foreach ($this->listTables() as $table) {
            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->values()->all();
            $tables[$table] = $rows;
        }

        $payload = [
            'meta' => [
                'database' => DB::getDatabaseName(),
                'driver' => DB::getDriverName(),
                'created_at' => now()->toIso8601String(),
            ],
            'tables' => $tables,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Unable to serialize database backup.');
        }

        $encrypted = Crypt::encryptString($json);
        Storage::disk($disk)->put($fileName, $encrypted);

        $checksum = hash('sha256', $json);
        $prunedPaths = $this->prune($retentionDays);

        return [
            'disk' => $disk,
            'path' => $fileName,
            'checksum' => $checksum,
            'encrypted' => true,
            'retention_days' => $retentionDays,
            'bytes' => strlen($json),
            'table_count' => count($tables),
            'record_count' => collect($tables)->sum(fn (array $rows) => count($rows)),
            'pruned_count' => count($prunedPaths),
        ];
    }

    public function restore(string $path): array
    {
        $disk = (string) config('ops.backup_disk', 'local');
        $contents = (string) Storage::disk($disk)->get($path);

        try {
            $json = Crypt::decryptString($contents);
        } catch (\Throwable) {
            $json = $contents;
        }

        $payload = json_decode($json, true);

        if (! is_array($payload) || ! isset($payload['tables']) || ! is_array($payload['tables'])) {
            throw new RuntimeException('Invalid backup payload.');
        }

        $tables = array_keys($payload['tables']);
        Schema::disableForeignKeyConstraints();

        try {
            foreach (array_reverse($tables) as $table) {
                DB::table($table)->delete();
            }

            foreach ($tables as $table) {
                $rows = $payload['tables'][$table] ?? [];

                foreach (array_chunk($rows, 500) as $chunk) {
                    if ($chunk !== []) {
                        DB::table($table)->insert($chunk);
                    }
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'table_count' => count($tables),
            'record_count' => collect($payload['tables'])->sum(fn (array $rows) => count($rows)),
        ];
    }

    /**
     * Remove backup snapshots older than the configured retention window.
     *
     * @return array<int, string>
     */
    public function prune(int $retentionDays): array
    {
        if ($retentionDays < 1) {
            return [];
        }

        $disk = (string) config('ops.backup_disk', 'local');
        $prefix = trim((string) config('ops.backup_prefix', 'backups'), '/');
        $cutoff = now()->subDays($retentionDays);
        $paths = $prefix !== ''
            ? Storage::disk($disk)->allFiles($prefix)
            : Storage::disk($disk)->allFiles();

        $deleted = [];

        foreach ($paths as $path) {
            if (! preg_match('/buildledger-db-(\d{8}_\d{6})\.json(?:\.enc)?$/', $path, $matches)) {
                continue;
            }

            try {
                $createdAt = Carbon::createFromFormat('Ymd_His', $matches[1]);
            } catch (\Throwable) {
                continue;
            }

            if ($createdAt->lessThan($cutoff)) {
                Storage::disk($disk)->delete($path);
                $deleted[] = $path;
            }
        }

        return $deleted;
    }

    /**
     * @return array<int, string>
     */
    private function listTables(): array
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("SELECT name as table_name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('table_name')
                ->all();
        }

        return collect(DB::select('SHOW TABLES'))
            ->map(function (object $row) {
                $values = array_values((array) $row);

                return $values[0] ?? null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
