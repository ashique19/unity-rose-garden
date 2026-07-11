<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlatBillTypeSetting extends Model
{
    protected $fillable = [
        'flat_id',
        'bill_type_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function billType(): BelongsTo
    {
        return $this->belongsTo(BillType::class);
    }
}
