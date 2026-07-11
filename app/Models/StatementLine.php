<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatementLine extends Model
{
    protected $fillable = [
        'monthly_statement_id',
        'bill_type_id',
        'bill_type_key',
        'label',
        'quantity',
        'rate',
        'amount',
        'note',
        'enabled',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'enabled' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(MonthlyStatement::class, 'monthly_statement_id');
    }

    public function billType(): BelongsTo
    {
        return $this->belongsTo(BillType::class);
    }
}
