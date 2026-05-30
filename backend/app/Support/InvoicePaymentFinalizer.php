<?php

namespace App\Support;

use App\Models\Payment;
use App\Notifications\InvoicePaid;
use App\Models\PaymentLedgerEntry;
use App\Models\AnalyticsEvent;

class InvoicePaymentFinalizer
{
    public function finalize(Payment $payment): void
    {
        $invoice = $payment->invoice()->with('user')->first();

        if (! $invoice) {
            return;
        }

        $totalPaid = (float) PaymentLedgerEntry::query()
            ->where('invoice_id', $invoice->id)
            ->whereIn('event_type', ['captured'])
            ->selectRaw('COALESCE(SUM(amount), 0) as aggregate')
            ->value('aggregate');

        $refunded = (float) PaymentLedgerEntry::query()
            ->where('invoice_id', $invoice->id)
            ->where('event_type', 'refunded')
            ->selectRaw('COALESCE(SUM(amount), 0) as aggregate')
            ->value('aggregate');

        $netPaid = max(0, $totalPaid - $refunded);

        if ($netPaid < $invoice->total || $invoice->status === 'Paid') {
            return;
        }

        $invoice->update([
            'status' => 'Paid',
            'public_payment_token' => null,
            'public_payment_token_expires_at' => null,
        ]);
        $invoice->user->notify(new InvoicePaid($invoice));

        AnalyticsEvent::create([
            'user_id' => $invoice->user_id,
            'event_name' => 'payment_completed',
            'path' => '/payments',
            'source' => 'backend',
            'properties' => [
                'invoice_id' => $invoice->id,
                'total_paid' => (float) $netPaid,
            ],
            'occurred_at' => now(),
        ]);
    }
}
