<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'company_id',
        'client_id',
        'contract_id',
        'invoice_number',
        'status',
        'sent_at',
        'public_payment_token',
        'public_payment_token_expires_at',
        'issue_date',
        'due_date',
        'notes',
        'subtotal',
        'tax',
        'discount',
        'total',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'public_payment_token_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function hasActivePaymentLink(): bool
    {
        return $this->status === 'Sent'
            && (bool) $this->public_payment_token
            && $this->public_payment_token_expires_at?->isFuture();
    }
}
