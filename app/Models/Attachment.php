<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function absoluteUrl(): string
    {
        return $this->url();
    }
}
