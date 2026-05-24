<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bill_for_month',
        'price_per_kg',
        'price_per_m3',
        'total_used_m3',  // Added
        'total_used_kg',  // Added
        'total_bill',     // Added
    ];

    protected $casts = [
        'bill_for_month' => 'date',
        'price_per_kg'  => 'decimal:2',
        'price_per_m3'  => 'decimal:2',
        'total_used_m3' => 'decimal:2', // Added
        'total_used_kg' => 'decimal:2', // Added
        'total_bill'    => 'decimal:2', // Added
    ];

    /**
     * Get all the breakdown details associated with this bill.
     */
    public function details(): HasMany
    {
        return $this->hasMany(BillDetail::class);
    }
}