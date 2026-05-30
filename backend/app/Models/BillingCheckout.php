<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingCheckout extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'gateway',
        'reference',
        'amount_ngn',
        'billing_interval',
        'status',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'billing_interval' => 'string',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
