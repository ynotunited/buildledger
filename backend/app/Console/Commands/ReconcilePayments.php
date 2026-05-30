<?php

namespace App\Console\Commands;

use App\Support\OperationalEventRecorder;
use App\Support\PaymentReconciliationService;
use Illuminate\Console\Command;
use Throwable;

class ReconcilePayments extends Command
{
    protected $signature = 'ops:reconcile-payments';

    protected $description = 'Reconcile payment ledger entries against payments, invoices, and subscriptions.';

    public function handle(PaymentReconciliationService $reconciliation, OperationalEventRecorder $events): int
    {
        if (! config('ops.reconciliation_enabled', true)) {
            $this->warn('Payment reconciliation is disabled.');

            $events->record([
                'category' => 'reconciliation',
                'severity' => 'warning',
                'title' => 'Payment reconciliation skipped.',
                'message' => 'Payment reconciliation is disabled by configuration.',
                'source' => 'ops:reconcile-payments',
                'context' => ['reason' => 'disabled'],
            ]);

            return self::SUCCESS;
        }

        try {
            $result = $reconciliation->run();
            $summary = $result['summary'];

            $this->info(sprintf(
                'Reconciled %d payments, %d subscriptions, found %d anomalies.',
                $summary['reconciled_payments'],
                $summary['reconciled_subscriptions'],
                $summary['anomalies']
            ));

            if ($summary['anomalies'] > 0) {
                $this->warn('Review the operational events feed for the reconciliation details.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $events->record([
                'category' => 'reconciliation',
                'severity' => 'critical',
                'title' => 'Payment reconciliation failed.',
                'message' => $exception->getMessage(),
                'source' => 'ops:reconcile-payments',
                'context' => ['error' => $exception->getMessage()],
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
