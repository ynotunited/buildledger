<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'ip_address',
        'path',
        'method',
        'user_agent',
        'identity_key',
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
}
