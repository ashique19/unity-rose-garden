<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomCharge extends Model
{
    protected $fillable = [
        'flat_id',
        'charge_month',
        'label',
        'amount',
        'notes',
    ];

    protected $casts = [
        'charge_month' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the flat associated with this custom charge.
     */
    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class, 'flat_id');
    }
}