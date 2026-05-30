<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpersonationEvent extends Model
{
    protected $fillable = [
        'impersonator_user_id',
        'target_user_id',
        'action',
        'note',
        'ip_address',
        'user_agent',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonator_user_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
