<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_id',
        'client_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_reference',
        'gateway_transaction_id',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(PaymentLedgerEntry::class);
    }

    public function latestLedgerEntry()
    {
        return $this->hasOne(PaymentLedgerEntry::class)->latestOfMany();
    }

    public function getStatusAttribute($value): string
    {
        $ledgerEntry = $this->relationLoaded('latestLedgerEntry')
            ? $this->getRelation('latestLedgerEntry')
            : $this->latestLedgerEntry()->first();

        if (! $ledgerEntry) {
            return (string) $value;
        }

        return match ((string) $ledgerEntry->event_type) {
            'captured', 'subscription_renewed' => 'Completed',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            'authorized', 'intent_created', 'gateway_initiated', 'processing' => 'Pending',
            default => (string) $value,
        };
    }
}
