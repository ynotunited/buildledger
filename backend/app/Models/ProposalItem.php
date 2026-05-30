<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalItem extends Model
{
    protected $fillable = [
        'proposal_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
