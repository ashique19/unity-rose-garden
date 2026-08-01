<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class BillType extends Model
{
    public const NATURE_METER_FLAT = 'meter_flat';

    public const NATURE_METER_COMMON = 'meter_common';

    public const NATURE_OTHER = 'other';

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

    public function scopeOtherCharges($query)
    {
        return $query->where('nature', self::NATURE_OTHER);
    }

    public function scopeCommonMeters($query)
    {
        return $query->where('nature', self::NATURE_METER_COMMON);
    }

    public function isCommonMeter(): bool
    {
        return $this->nature === self::NATURE_METER_COMMON;
    }

    public function isMeterFlat(): bool
    {
        return $this->nature === self::NATURE_METER_FLAT;
    }

    /**
     * @return Collection<int, self>
     */
    public static function activeCommonMeters(): Collection
    {
        return static::query()
            ->commonMeters()
            ->where('is_active', true)
            ->ordered()
            ->get();
    }

    /**
     * @return list<string>
     */
    public static function commonMeterKeys(): array
    {
        return static::activeCommonMeters()->pluck('key')->all();
    }

    /**
     * Keys excluded from template / custom-charge flows (gas + common meters).
     *
     * @return list<string>
     */
    public static function reservedChargeKeys(): array
    {
        return array_values(array_unique(array_merge(
            ['gas'],
            static::commonMeterKeys()
        )));
    }
}
