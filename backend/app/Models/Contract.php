<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'client_id',
        'proposal_id',
        'title',
        'body_content',
        'status',
        'sent_at',
        'signing_token',
        'signing_token_expires_at',
        'client_signature_name',
        'client_signature_ip',
        'client_signed_at',
    ];

    protected $hidden = [
        'user_id',
        'client_signature_ip',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'signing_token_expires_at' => 'datetime',
            'client_signed_at' => 'datetime',
        ];
    }

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

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function hasActiveSigningLink(): bool
    {
        return $this->status === 'Sent'
            && (bool) $this->signing_token
            && $this->signing_token_expires_at?->isFuture();
    }
}
