<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyRecord extends Model
{
    protected $fillable = [
        'user_id',
        'scope',
        'idempotency_key',
        'request_hash',
        'status',
        'response_status',
        'response_payload',
        'resource_type',
        'resource_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
