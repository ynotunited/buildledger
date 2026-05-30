<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLedgerEntry extends Model
{
    protected $fillable = [
        'user_id',
        'payment_id',
        'billing_checkout_id',
        'subscription_id',
        'invoice_id',
        'gateway',
        'event_type',
        'gateway_event_id',
        'gateway_reference',
        'dedupe_key',
        'amount',
        'currency',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function billingCheckout()
    {
        return $this->belongsTo(BillingCheckout::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
