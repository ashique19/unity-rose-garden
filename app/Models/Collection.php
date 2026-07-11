<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collection extends Model
{
    protected $fillable = [
        'monthly_statement_id',
        'amount',
        'collected_on',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'collected_on' => 'date',
        ];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(MonthlyStatement::class, 'monthly_statement_id');
    }

    public function ledgerEntry(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AccountLedgerEntry::class);
    }
}
