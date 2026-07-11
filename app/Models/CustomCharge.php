<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomCharge extends Model
{
    protected $fillable = [
        'flat_id',
        'bill_type_id',
        'charge_month',
        'label',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'charge_month' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class, 'flat_id');
    }

    public function billType(): BelongsTo
    {
        return $this->belongsTo(BillType::class);
    }
}
