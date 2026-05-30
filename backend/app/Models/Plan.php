<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price_ngn',
        'price_annually_ngn',
        'billing_interval',
        'features',
        'company_limit',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'price_annually_ngn' => 'integer',
        ];
    }

    public function priceForInterval(string $interval): int
    {
        $interval = in_array($interval, ['monthly', 'annual'], true) ? $interval : 'monthly';

        if ($interval === 'annual') {
            return (int) ($this->price_annually_ngn ?? ($this->price_ngn * 12));
        }

        return (int) $this->price_ngn;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function billingCheckouts()
    {
        return $this->hasMany(BillingCheckout::class);
    }
}
