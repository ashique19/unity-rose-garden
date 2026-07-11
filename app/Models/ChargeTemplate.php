<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeTemplate extends Model
{
    protected $fillable = [
        'bill_type_id',
        'charge_key',
        'label',
        'default_amount',
        'is_building_wide',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_building_wide' => 'boolean',
        ];
    }

    public function billType(): BelongsTo
    {
        return $this->belongsTo(BillType::class);
    }
}
