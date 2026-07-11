<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MonthlyStatement extends Model
{
    protected $fillable = [
        'flat_id',
        'bill_month',
    ];

    protected function casts(): array
    {
        return [
            'bill_month' => 'date',
        ];
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StatementLine::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function enabledLines(): HasMany
    {
        return $this->lines()->where('enabled', true);
    }

    public function totalAmount(): string
    {
        return (string) $this->enabledLines()->sum('amount');
    }

    public function collectedAmount(): string
    {
        return (string) $this->collections()->sum('amount');
    }

    public function pendingAmount(): string
    {
        $pending = (float) $this->totalAmount() - (float) $this->collectedAmount();

        return number_format(max(0, $pending), 2, '.', '');
    }

    public function gasLine(): ?StatementLine
    {
        return $this->lines->firstWhere('bill_type_key', 'gas');
    }

    public function otherLines()
    {
        return $this->lines->where('bill_type_key', '!=', 'gas')->values();
    }

    public static function monthKey(?Carbon $date = null): string
    {
        return ($date ?? now())->copy()->startOfMonth()->toDateString();
    }
}
