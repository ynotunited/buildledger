<?php

namespace App\Console\Commands;

use App\Support\DeploymentHealthChecker;
use Illuminate\Console\Command;

class SecurityDeploymentCheck extends Command
{
    protected $signature = 'security:deployment-check';

    protected $description = 'Validate production security and operational readiness settings.';

    public function handle(DeploymentHealthChecker $checker): int
    {
        $report = $checker->check();

        $this->info('Deployment readiness: '.$report['status']);
        $this->newLine();

        foreach ($report['checks'] as $name => $check) {
            $label = strtoupper($check['status']);
            $this->line(sprintf('[%s] %s: %s', $label, $name, $check['message']));

            if ($check['context'] !== []) {
                $this->line('  '.json_encode($check['context'], JSON_UNESCAPED_SLASHES));
            }
        }

        return $report['status'] === 'fail' ? self::FAILURE : self::SUCCESS;
    }
}
