<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'size',
    ];

    protected $appends = ['url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Generate a temporary signed URL (S3/R2) or a local URL.
     */
    public function getUrlAttribute(): string
    {
        if ($this->disk === 's3' || $this->disk === 'r2') {
            return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(60));
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
