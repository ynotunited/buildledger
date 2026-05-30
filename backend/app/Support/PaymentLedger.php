<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use Carbon\CarbonInterface;

class PaymentLedger
{
    public function append(array $attributes): PaymentLedgerEntry
    {
        $dedupeKey = $attributes['dedupe_key'] ?? null;
        $gatewayEventId = $attributes['gateway_event_id'] ?? null;

        if (is_string($dedupeKey) && $dedupeKey !== '') {
            $existing = PaymentLedgerEntry::query()->where('dedupe_key', $dedupeKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        if (is_string($gatewayEventId) && $gatewayEventId !== '') {
            $existing = PaymentLedgerEntry::query()->where('gateway_event_id', $gatewayEventId)->first();
            if ($existing) {
                return $existing;
            }
        }

        return PaymentLedgerEntry::create([
            'user_id' => $attributes['user_id'] ?? null,
            'payment_id' => $attributes['payment_id'] ?? null,
            'billing_checkout_id' => $attributes['billing_checkout_id'] ?? null,
            'subscription_id' => $attributes['subscription_id'] ?? null,
            'invoice_id' => $attributes['invoice_id'] ?? null,
            'gateway' => $attributes['gateway'] ?? null,
            'event_type' => $attributes['event_type'],
            'gateway_event_id' => $gatewayEventId,
            'gateway_reference' => $attributes['gateway_reference'] ?? null,
            'dedupe_key' => (string) $dedupeKey,
            'amount' => $attributes['amount'] ?? null,
            'currency' => $attributes['currency'] ?? 'NGN',
            'payload' => $attributes['payload'] ?? null,
            'occurred_at' => $attributes['occurred_at'] ?? now(),
        ]);
    }

    public function statusForPayment(Payment $payment): string
    {
        $ledgerEntry = $payment->relationLoaded('latestLedgerEntry')
            ? $payment->getRelation('latestLedgerEntry')
            : $payment->latestLedgerEntry()->first();

        if (! $ledgerEntry) {
            return (string) $payment->getRawOriginal('status');
        }

        return $this->statusForEvent((string) $ledgerEntry->event_type, (string) $payment->getRawOriginal('status'));
    }

    public function netCapturedForInvoice(Invoice $invoice): float
    {
        $captured = (float) PaymentLedgerEntry::query()
            ->where('invoice_id', $invoice->id)
            ->where('event_type', 'captured')
            ->selectRaw('COALESCE(SUM(amount), 0) as aggregate')
            ->value('aggregate');

        $refunded = (float) PaymentLedgerEntry::query()
            ->where('invoice_id', $invoice->id)
            ->where('event_type', 'refunded')
            ->selectRaw('COALESCE(SUM(amount), 0) as aggregate')
            ->value('aggregate');

        return max(0, $captured - $refunded);
    }

    public function netCapturedForUser(int $userId): float
    {
        return $this->netCapturedSince(null, $userId);
    }

    public function netCapturedSince(?CarbonInterface $since = null, ?int $userId = null): float
    {
        $capturedQuery = PaymentLedgerEntry::query()
            ->where('event_type', 'captured');

        $refundedQuery = PaymentLedgerEntry::query()
            ->where('event_type', 'refunded');

        if ($since) {
            $capturedQuery->where('occurred_at', '>=', $since);
            $refundedQuery->where('occurred_at', '>=', $since);
        }

        if ($userId !== null) {
            $capturedQuery->where('user_id', $userId);
            $refundedQuery->where('user_id', $userId);
        }

        $captured = (float) $capturedQuery->selectRaw('COALESCE(SUM(amount), 0) as aggregate')->value('aggregate');
        $refunded = (float) $refundedQuery->selectRaw('COALESCE(SUM(amount), 0) as aggregate')->value('aggregate');

        return max(0, $captured - $refunded);
    }

    public function statusForEvent(string $eventType, string $fallback = 'Pending'): string
    {
        return match ($eventType) {
            'captured', 'subscription_renewed' => 'Completed',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            'authorized', 'intent_created', 'gateway_initiated', 'processing' => 'Pending',
            default => $fallback,
        };
    }
}
