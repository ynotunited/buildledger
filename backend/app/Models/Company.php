<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'website',
        'tax_id',
        'logo_path',
        'logo_disk',
    ];

    protected $hidden = [
        'user_id',
        'logo_path',
        'logo_disk',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path || ! $this->logo_disk) {
            return null;
        }

        return Storage::disk($this->logo_disk)->url($this->logo_path);
    }
}
