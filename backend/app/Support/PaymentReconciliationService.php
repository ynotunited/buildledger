<?php

namespace App\Support;

use App\Models\BillingCheckout;
use App\Models\PaymentLedgerEntry;
class PaymentReconciliationService
{
    public function __construct(
        private readonly InvoicePaymentFinalizer $finalizer,
        private readonly SubscriptionBillingManager $billingManager,
        private readonly OperationalEventRecorder $eventRecorder
    ) {
    }

    public function run(): array
    {
        $windowHours = max(1, (int) config('ops.reconciliation_window_hours', 48));
        $since = now()->subHours($windowHours);
        $anomalies = [];
        $reconciledPayments = 0;
        $reconciledSubscriptions = 0;

        $ledgerEntries = PaymentLedgerEntry::query()
            ->where('occurred_at', '>=', $since)
            ->whereIn('event_type', ['captured', 'subscription_renewed'])
            ->with(['payment.invoice.client', 'invoice.client', 'subscription.plan', 'billingCheckout'])
            ->orderBy('id')
            ->get();

        foreach ($ledgerEntries as $entry) {
            if ($entry->payment) {
                $payment = $entry->payment->fresh(['invoice', 'client', 'latestLedgerEntry']);

                if ($payment) {
                    $this->finalizer->finalize($payment);
                    $reconciledPayments++;
                }
            }

            if ($entry->subscription) {
                $subscription = $entry->subscription->fresh(['user', 'plan']);
                $interval = $subscription ? $this->billingManager->normalizeInterval($subscription->billing_interval ?? 'monthly') : 'monthly';
                $reference = (string) ($entry->gateway_reference ?? data_get($entry->payload, 'checkout_reference', $entry->dedupe_key));
                $gateway = (string) ($entry->gateway ?? 'internal');

                if ($subscription && (
                    $subscription->status !== 'active'
                    || ! $subscription->current_period_ends_at
                    || $subscription->current_period_ends_at->isPast()
                )) {
                    $this->billingManager->activateOrRenew(
                        $subscription->user,
                        $subscription->plan,
                        $gateway,
                        $interval,
                        $reference,
                        is_string(data_get($entry->payload, 'transaction_id')) ? (string) data_get($entry->payload, 'transaction_id') : null,
                        [
                            'source' => 'ops:reconcile-payments',
                            'ledger_entry_id' => $entry->id,
                            'checkout_id' => data_get($entry->payload, 'checkout_id'),
                            'webhook_reference' => $reference,
                        ]
                    );
                    $reconciledSubscriptions++;
                }
            }

            if (! $entry->payment_id && ! $entry->subscription_id) {
                $anomalies[] = [
                    'type' => 'orphaned_ledger_entry',
                    'ledger_entry_id' => $entry->id,
                    'gateway_reference' => $entry->gateway_reference,
                    'event_type' => $entry->event_type,
                ];
            }
        }

        $staleCheckouts = BillingCheckout::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', $since)
            ->latest('id')
            ->get();

        foreach ($staleCheckouts as $checkout) {
            $anomalies[] = [
                'type' => 'stale_checkout',
                'checkout_id' => $checkout->id,
                'reference' => $checkout->reference,
                'gateway' => $checkout->gateway,
                'amount_ngn' => (float) $checkout->amount_ngn,
                'age_hours' => $checkout->created_at?->diffInHours(now()),
            ];
        }

        $summary = [
            'window_hours' => $windowHours,
            'ledger_entries_checked' => $ledgerEntries->count(),
            'reconciled_payments' => $reconciledPayments,
            'reconciled_subscriptions' => $reconciledSubscriptions,
            'stale_checkouts' => $staleCheckouts->count(),
            'anomalies' => count($anomalies),
        ];

        $event = $this->eventRecorder->record([
            'category' => 'reconciliation',
            'severity' => $anomalies ? 'warning' : 'success',
            'title' => $anomalies
                ? 'Payment reconciliation found issues.'
                : 'Payment reconciliation completed successfully.',
            'message' => $anomalies
                ? 'The reconciliation job found mismatches that need review.'
                : 'Ledger and operational payment records are aligned.',
            'source' => 'ops:reconcile-payments',
            'context' => array_merge($summary, [
                'anomalies_sample' => array_slice($anomalies, 0, 10),
            ]),
            'resolved_at' => $anomalies ? null : now(),
        ]);

        return [
            'summary' => $summary,
            'anomalies' => $anomalies,
            'event' => $event,
        ];
    }
}
