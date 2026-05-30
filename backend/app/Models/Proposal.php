<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'client_id',
        'title',
        'status',
        'issue_date',
        'expiry_date',
        'notes',
        'subtotal',
        'tax',
        'total',
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

    public function items()
    {
        return $this->hasMany(ProposalItem::class);
    }
}
