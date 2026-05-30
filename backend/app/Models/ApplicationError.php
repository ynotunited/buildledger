<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationError extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'level',
        'message',
        'exception_class',
        'path',
        'ip_address',
        'user_agent',
        'request_id',
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
