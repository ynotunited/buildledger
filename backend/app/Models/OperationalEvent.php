<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalEvent extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'severity',
        'title',
        'message',
        'source',
        'reference_type',
        'reference_id',
        'context',
        'occurred_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
