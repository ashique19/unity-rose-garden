<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    protected $fillable = [
        'expense_head_id',
        'amount',
        'entry_date',
        'payee',
        'note',
        'balance_before',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(AccountLedgerEntry::class);
    }
}
