<?php

namespace App\Console\Commands;

use App\Support\DeploymentHealthChecker;
use App\Support\OperationalEventRecorder;
use Illuminate\Console\Command;

class HealthScan extends Command
{
    protected $signature = 'ops:health-scan';

    protected $description = 'Run deployment readiness checks and record operational alerts.';

    public function handle(DeploymentHealthChecker $checker, OperationalEventRecorder $events): int
    {
        $report = $checker->check();
        $severity = $report['status'] === 'failed'
            ? 'critical'
            : ($report['status'] === 'warning' ? 'warning' : 'success');

        $events->record([
            'category' => 'monitoring',
            'severity' => $severity,
            'title' => 'Deployment health scan completed.',
            'message' => $report['status'] === 'ok'
                ? 'All deployment checks passed.'
                : 'One or more deployment checks need attention.',
            'source' => 'ops:health-scan',
            'context' => $report,
            'resolved_at' => $report['status'] === 'ok' ? now() : null,
        ]);

        $this->line('Deployment readiness: ' . $report['status']);

        foreach ($report['checks'] as $name => $check) {
            $this->line(sprintf(
                '- %s: %s - %s',
                $name,
                strtoupper((string) $check['status']),
                $check['message']
            ));
        }

        return $report['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
