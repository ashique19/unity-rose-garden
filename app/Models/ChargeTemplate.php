<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeTemplate extends Model
{
    protected $fillable = [
        'charge_key',
        'label',
        'default_amount',
        'is_building_wide',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_building_wide' => 'boolean',
    ];
}