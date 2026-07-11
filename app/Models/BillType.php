<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillType extends Model
{
    protected $fillable = [
        'key',
        'label',
        'nature',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function flatSettings(): HasMany
    {
        return $this->hasMany(FlatBillTypeSetting::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}
