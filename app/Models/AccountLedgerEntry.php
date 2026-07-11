<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLedgerEntry extends Model
{
    public const TYPE_CASH_IN = 'cash_in';

    public const TYPE_CASH_OUT = 'cash_out';

    protected $fillable = [
        'type',
        'amount',
        'entry_date',
        'flat_id',
        'collection_id',
        'expense_id',
        'category',
        'expense_head_id',
        'payee',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
        ];
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function isCashIn(): bool
    {
        return $this->type === self::TYPE_CASH_IN;
    }

    public function isCashOut(): bool
    {
        return $this->type === self::TYPE_CASH_OUT;
    }
}
