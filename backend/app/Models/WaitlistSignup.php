<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistSignup extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVATED = 'activated';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'email',
        'source',
        'ip_address',
        'user_agent',
        'status',
        'approved_at',
        'approved_by_user_id',
        'activated_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'activated_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ACTIVATED], true);
    }
}
