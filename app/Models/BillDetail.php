<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'flat_id',
        'previous_reading',
        'current_reading',
        'used_m3',
        'used_kg',
        'bill_for_month',
    ];

    protected $casts = [
        'bill_for_month'   => 'date',
        'previous_reading' => 'decimal:2',
        'current_reading'  => 'decimal:2',
        'used_m3'          => 'decimal:2',
        'used_kg'          => 'decimal:2',
    ];

    /**
     * Get the parent bill configuration that owns this detail log.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the flat associated with this specific bill breakdown.
     */
    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }
}