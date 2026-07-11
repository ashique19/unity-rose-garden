<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = [
        'name',
        'm3_to_kg_rate',
        'opening_balance',
    ];

    protected function casts(): array
    {
        return [
            'm3_to_kg_rate' => 'decimal:4',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function balance(): string
    {
        $cashIn = (float) AccountLedgerEntry::query()
            ->where('type', AccountLedgerEntry::TYPE_CASH_IN)
            ->sum('amount');

        $cashOut = (float) AccountLedgerEntry::query()
            ->where('type', AccountLedgerEntry::TYPE_CASH_OUT)
            ->sum('amount');

        $balance = (float) $this->opening_balance + $cashIn - $cashOut;

        return number_format($balance, 2, '.', '');
    }

    /**
     * Sum of pending amounts across all monthly statements.
     */
    public static function totalPendingCollections(): string
    {
        $statements = MonthlyStatement::query()
            ->with(['lines', 'collections'])
            ->get();

        $pending = $statements->sum(fn (MonthlyStatement $s) => (float) $s->pendingAmount());

        return number_format($pending, 2, '.', '');
    }
}
