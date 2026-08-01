<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Model
{
    protected $fillable = [
        'public_token',
        'title',
        'original_name',
        'path',
        'mime',
        'size_bytes',
        'width',
        'height',
        'bill_month',
        'note',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_month' => 'date',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $attachment) {
            if (empty($attachment->public_token)) {
                $attachment->public_token = Str::random(40);
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Public shareable URL served by Laravel (does not depend on storage symlink).
     */
    public function url(): string
    {
        return route('attachments.media', $this->public_token);
    }

    public function absoluteUrl(): string
    {
        return $this->url();
    }

    public function diskPath(): string
    {
        return Storage::disk('public')->path($this->path);
    }

    public function existsOnDisk(): bool
    {
        return $this->path !== '' && Storage::disk('public')->exists($this->path);
    }
}
